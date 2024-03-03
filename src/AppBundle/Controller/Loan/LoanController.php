<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\CoreLoan;
use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\LoanCheckOutType;
use AppBundle\Helpers\UnitTestHelper;
use phpDocumentor\Reflection\Types\True_;
use Postmark\Models\PostmarkAttachment;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LoanController
 * @package AppBundle\Controller
 */
class LoanController extends Controller
{

    /**
     * @param $loanId
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Route("loan/{loanId}", requirements={"loanId": "\d+"}, name="public_loan")
     */
    public function showLoanAction($loanId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        /** @var \AppBundle\Services\Loan\CheckoutService $checkoutService */
        $checkoutService = $this->get('service.checkout');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Debug\DebugService $debugService */
        $debugService = $this->get('service.debug');

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        if ($this->getUser()->getId() != $loan->getContact()->getId() && !$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "This is not your loan");
            return $this->redirectToRoute('home');
        }

        // Switch the web session to requested user
        if ($user->getId() != $loan->getContact()->getId()) {
            $this->get('session')->set('sessionUserId', $loan->getContact()->getId());
        }

        $contactBalance = $loan->getContact()->getBalance();

        // Check mandatory custom fields
        $canCheckOut = true;
        $missingFields = $contactService->checkRequiredCustomFields($loan->getContact());
        if ($missingFields !== true && $user->hasRole("ROLE_ADMIN")) {
            $canCheckOut = false;
            $this->addFlash('error', $loan->getContact()->getName()." is missing required data:");
            foreach ($missingFields AS $fieldName) {
                $this->addFlash('error', "- {$fieldName}");
            }
            $this->addFlash('error', '<br><a class="btn btn-primary" href="/admin/contact/'.$loan->getContact()->getId().'">Edit contact</a>');
        }

        // Don't handle overdue accounts in this process
        if ($contactBalance < 0) {
            $contactBalance = 0;
        }

        // If contact has balance still to charge to account, apply it to this loan
        $loanBalance = $loan->getBalance() - $contactBalance;
        if ($loanBalance < 0) {
            $loanBalance = 0;
        }
        $subtotal = $loanBalance;

        $paymentDue = 0;
        if (in_array($loan->getStatus(), ['PENDING', 'RESERVED'])) {
            $paymentDue = $loanBalance + $loan->getTotalDeposits();
        }

        $form = $this->createForm(LoanCheckOutType::class, null, array(
            'em' => $em,
            'stripePaymentMethodId' => $stripePaymentMethodId,
            'user' => $this->getUser(),
            'attr' => [
                'id' => 'form-loan'
            ],
            'paymentDue' => $paymentDue
        ));

        // HANDLE THE FORM NOW

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $debugService->stripeDebug('Payment form submitted', print_r($_REQUEST, true));

            // Until we have a fail, assume payment is OK
            $paymentOk = true;
            $cardDetails = null;

            // Sum deposits to take off total payment amount
            $totalDeposits = 0;

            $paymentAmount = $form->get('paymentAmount')->getData();
            $paymentMethod = $form->get('paymentMethod')->getData();

            $loanRows = [];
            foreach ($loan->getLoanRows() AS $row) {
                $rowId = $row->getId();
                $loanRows[$rowId] = $row;
            }

            // Sum the deposits
            if ($deposits = $request->request->get('deposits')) {
                foreach ($deposits AS $loanRowId => $amount) {
                    $totalDeposits += $amount;
                }
            }

            // Amount including deposits
            if ($paymentAmount > 0) {

                // Take the deposit amount off
                $loanAmount = $paymentAmount - $totalDeposits;

                // Create the payment for the loan itself
                if ($loanAmount > 0) {

                    if ($paymentId = $request->get('paymentId')) {
                        // We've created a payment via Stripe payment intent, link it to the loan
                        $payments = $paymentService->get(['id' => $paymentId]);
                        $payment = $payments[0];
                    } else {
                        // No existing payment exists
                        $payment = new Payment();

                        $debugService->stripeDebug('Payment() initialized with empty (No psp code)');
                    }

                    $payment->setCreatedBy($user);

                    if ($paymentMethod !== null) {
                        $payment->setPaymentMethod($paymentMethod);
                    }

                    $payment->setAmount($loanAmount);
                    $paymentNote = Payment::TEXT_PAYMENT_RECEIVED.'. '.$form->get('paymentNote')->getData();
                    $payment->setNote($paymentNote);
                    $payment->setContact($loan->getContact());
                    $payment->setType(Payment::PAYMENT_TYPE_PAYMENT);
                    $payment->setLoan($loan);

                    if (!$paymentService->create($payment)) {
                        $paymentOk = false;
                        foreach ($paymentService->errors AS $error) {
                            $this->addFlash('error', $error);
                        }
                    }
                }

                // Create the deposits as separate payments
                if ($paymentOk == true) {

                    $debugService->stripeDebug(
                        'Create the deposits as separate payments',
                        [
                            'deposits' => $request->request->get('deposits')
                        ]
                    );

                    if ($deposits = $request->request->get('deposits')) {

                        foreach ($deposits AS $loanRowId => $amount) {

                            if ($amount > 0 && $paymentOk == true) {
                                $p = new Payment();
                                $p->setType(Payment::PAYMENT_TYPE_DEPOSIT);
                                $p->setCreatedBy($user);
                                $p->setPaymentMethod($paymentMethod);
                                $p->setAmount($amount);
                                $paymentNote = 'Deposit received for "'.$loanRows[$loanRowId]->getInventoryItem()->getName().'".';
                                $p->setNote($paymentNote);
                                $p->setContact($loan->getContact());

                                $p->setLoanRow($loanRows[$loanRowId]);
                                $p->setIsDeposit(true); // Creates deposit, payment and links to loan row

                                // This can link multiple payments to one Stripe charge
                                if ($chargeId = $request->get('chargeId')) {
                                    $p->setPspCode($chargeId);
                                }

                                if (!$paymentService->create($p)) {
                                    $paymentOk = false;
                                    foreach ($paymentService->errors AS $error) {
                                        $this->addFlash('error', $error);
                                    }
                                }
                            }

                        }
                    }
                }

            }

            // pre-check before we start creating payments
            if (!$checkoutService->validateCheckout($loan)) {

                // Return error only for the unit test because it doesn't support flash bags
                if (UnitTestHelper::isUnitTestEnvironment() && UnitTestHelper::isCommandLine()) {

                    $message = "We can't check out:" . PHP_EOL;

                    foreach ($checkoutService->errors as $error) {
                        $message .= $error . PHP_EOL;
                    }

                    return $this->render('unit_test/display_message.html.twig', [
                            'message' => $message
                        ]
                    );

                }

                $this->addFlash('error', "We can't check out:");
                foreach ($checkoutService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);
            }

            // We either have a successful charge, or no payment amount
            if ($paymentOk == true) {

                $this->get('session')->set('pendingPaymentType', null);

                if ( $checkoutService->loanCheckOut($loan) ) {
                    $this->addFlash('success', "Items are now checked out.");

                    $this->sendCheckoutConfirmationEmail($loan);

                    foreach ($loan->getLoanRows() AS $row) {
                        if ($row->getInventoryItem()->getDonatedBy()) {
                            $this->sendDonorEmail($row);
                        }
                    }

                    $this->addLoanToCore($loan);
                } else {
                    $this->addFlash('error', "We can't check out this loan:");
                    foreach ($checkoutService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }
            } else {
                // we will have errors from the payment handler
                $this->addFlash('error', "There were payment errors, the loan was not checked out");
            }

            return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

        }

        $contact = $contactService->loadCustomerCards($loan->getContact());
        $loan->setContact($contact);

        return $this->render('member_site/loan/loan.html.twig', [
                'form' => $form->createView(),
                'loan' => $loan,
                'user' => $loan->getContact(),
                'subtotal' => $subtotal,
                'payment_due' => $paymentDue,
                'canCheckOut' => $canCheckOut
            ]
        );
    }

    /**
     * @param Loan $loan
     * @return bool
     *
     * When we get super busy we'll make this asynchronous
     */
    private function addLoanToCore(Loan $loan) {
        try {

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Services\SettingsService $settingsService */
            $settingsService = $this->get('settings');

            if ($tenant = $settingsService->getTenant(false)) {
                foreach ($loan->getLoanRows() AS $row) {
                    /** @var $row \AppBundle\Entity\LoanRow */
                    $item = $row->getInventoryItem();

                    $s3_bucket = $this->get('service.tenant')->getS3Bucket();
                    $schema    = $this->get('service.tenant')->getSchema();
                    $imageUrl  = $s3_bucket.$schema.'/thumbs/'.$item->getImageName();

                    $nameParts = explode(' ', $loan->getContact()->getName());

                    // Create a new entry
                    $coreLoan = new CoreLoan();
                    $coreLoan->setCreatedAt(new \DateTime());
                    $coreLoan->setImage($imageUrl);
                    $coreLoan->setItem($item->getName());
                    $coreLoan->setMember($nameParts[0]);
                    $coreLoan->setLibrary($tenant);

                    $em->persist($coreLoan);
                    $em->flush();
                }
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param LoanRow $loanRow
     * @return bool
     */
    private function sendDonorEmail(LoanRow $loanRow)
    {
        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        // Send email confirmation
        $toEmail = $loanRow->getInventoryItem()->getDonatedBy()->getEmail();
        $toName = $loanRow->getInventoryItem()->getDonatedBy()->getName();

        if ($toEmail) {

            $locale = $loanRow->getInventoryItem()->getDonatedBy()->getLocale();

            // Save and switch locale for sending the email
            $sessionLocale = $this->get('translator')->getLocale();
            $this->get('translator')->setLocale($locale);

            $message = $this->renderView(
                'emails/loan_donor_notify.html.twig', [
                    'loanRow' => $loanRow
                ]
            );

            if (!$subject = $this->get('settings')->getSettingValue('email_donor_notification_subject')) {
                $subject = $this->get('translator')->trans('le_email.donor_notify.subject', [], 'emails', $locale);
            }

            // Send the email
            if (!$emailService->send($toEmail, $toName, $subject, $message, false)) {
                foreach ($emailService->getErrors() AS $msg) {
                    $this->addFlash('error', $msg);
                }
            }

            // Revert locale for the UI
            $this->get('translator')->setLocale($sessionLocale);

            return true;
        }

        return false;

    }

    /**
     * @param Loan $loan
     * @return bool
     */
    private function sendCheckoutConfirmationEmail(Loan $loan)
    {
        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $token = $contactService->generateAccessToken($loan->getContact());

        $loginUri = $tenantService->getTenant(false)->getDomain(true);
        $loginUri .= '/access?t='.$token.'&e='.urlencode($loan->getContact()->getEmail());
        $loginUri .= '&r=/loan/'.$loan->getId();

        // Send email confirmation
        $toEmail = $loan->getContact()->getEmail();
        $toName = $loan->getContact()->getName();

        if ($toEmail) {

            $locale = $loan->getContact()->getLocale();

            // Save and switch locale for sending the email
            $sessionLocale = $this->get('translator')->getLocale();
            $this->get('translator')->setLocale($locale);

            $message = $this->renderView(
                'emails/loan_checkout.html.twig',
                [
                    'loanRows' => $loan->getLoanRows(),
                    'includeButton' => true,
                    'loginUri' => $loginUri
                ]
            );

            if (!$subject = $this->get('settings')->getSettingValue('email_loan_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.checkout.subject', [], 'emails', $locale);
            }

            $subject .= " (Ref ".$loan->getId().")";

            $emailService->send($toEmail, $toName, $subject, $message, true);

            // Revert locale for the UI
            $this->get('translator')->setLocale($sessionLocale);

            return true;

        }

        return true;
    }

}
