<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Contact;
use AppBundle\Entity\CoreLoan;
use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Loan;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\LoanCheckOutType;
use Postmark\Models\PostmarkAttachment;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Manual loan checkout tests:
 * With and without checkout prompts
 * With and without deposits
 * Multiple items
 * Regular payment methods, and Stripe payments
 * Stripe success and Stripe failuer
 * Stripe re-use of previous cards
 * Email confirmation
 */


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

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

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
            'attr' => [
                'id' => 'form-loan'
            ],
            'paymentDue' => $paymentDue
        ));

        // HANDLE THE FORM NOW

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // pre-check before we start creating payments
            if (!$checkoutService->validateCheckout($loan)) {
                $this->addFlash('error', "We can't check out:");
                foreach ($checkoutService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);
            }

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

            if ($paymentAmount > 0) {

                $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

                if ($stripePaymentMethodId == $paymentMethod->getId()) {
                    $cardDetails = [
                        'token'  => $form->get('stripeToken')->getData(),
                        'cardId' => $form->get('stripeCardId')->getData(),
                    ];
                }

                // Take the deposit amount off
                $paymentAmount -= $totalDeposits;

                // Create the payment for the loan itself
                if ($paymentAmount > 0) {
                    $payment = new Payment();
                    $payment->setCreatedBy($user);
                    $payment->setPaymentMethod($paymentMethod);
                    $payment->setAmount($paymentAmount);
                    $paymentNote = Payment::TEXT_PAYMENT_RECEIVED.'. '.$form->get('paymentNote')->getData();
                    $payment->setNote($paymentNote);
                    $payment->setContact($loan->getContact());
                    $payment->setType(Payment::PAYMENT_TYPE_PAYMENT);
                    $payment->setLoan($loan);

                    if (!$paymentService->create($payment, $cardDetails)) {
                        $paymentOk = false;
                        foreach ($paymentService->errors AS $error) {
                            $this->addFlash('error', $error);
                        }
                    }

                    // unset the token so we can't use it for deposits
                    if (isset($cardDetails['token']) && $cardDetails['token']) {
                        unset($cardDetails['token']);
                    }
                }

                // Create the deposits as separate payments
                // Retrieve the cardId from the customer (if we've saved it with the first payment)
                if ($paymentOk == true) {
                    if ($deposits = $request->request->get('deposits')) {

                        // If we're using a new card for the payment, we can't reuse the token so get the saved card
                        if (isset($cardDetails['token']) && $cardDetails['token']) {
                            // we still have the token from Stripe Checkout (fee was zero)
                        } else if (!$cardDetails['cardId'] && $stripePaymentMethodId == $paymentMethod->getId()) {
                            $contact = $this->loadCustomerCards($loan->getContact());
                            $cards = $contact->getCreditCards();
                            if (is_array($cards) && count($cards) > 0) {
                                $cardId = $cards[0]->getCardId();
                                $cardDetails = [
                                    'cardId' => $cardId
                                ];
                            } else {
                                $this->addFlash('error', "Customer has no credit cards saved to take a deposit.");
                                $paymentOk = false;
                            }
                        }

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

                                if (!$paymentService->create($p, $cardDetails)) {
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

            // We either have a successful charge, or no payment amount
            if ($paymentOk == true) {
                if ( $checkoutService->loanCheckOut($loan) ) {
                    $this->addFlash('success', "Items are now checked out.");
                    $this->sendCheckoutConfirmationEmail($loan);
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

        $contact = $this->loadCustomerCards($loan->getContact());
        $loan->setContact($contact);

        return $this->render('member_site/pages/loan.html.twig', [
                'form' => $form->createView(),
                'loan' => $loan,
                'user' => $loan->getContact(),
                'subtotal' => $subtotal,
                'payment_due' => $paymentDue
            ]
        );
    }

    /**
     * @param Contact $contact
     * @return Contact
     */
    private function loadCustomerCards(Contact $contact) {
        // Get existing cards for a customer
        $stripeUseSavedCards = $this->get('settings')->getSettingValue('stripe_use_saved_cards');

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        $customerStripeId = $contact->getStripeCustomerId();
        if ($customerStripeId && $stripeUseSavedCards) {
            // Retrieve their cards
            $stripeCustomer = $stripeService->getCustomerById($customerStripeId);

            if (isset($stripeCustomer['sources']['data'])) {
                foreach($stripeCustomer['sources']['data'] AS $source) {
                    $creditCard = new CreditCard();
                    $creditCard->setLast4($source['last4']);
                    $creditCard->setExpMonth($source['exp_month']);
                    $creditCard->setExpYear($source['exp_year']);
                    $creditCard->setBrand($source['brand']);
                    $creditCard->setCardId($source['id']);
                    $contact->addCreditCard($creditCard);
                }
            }
        }

        return $contact;
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

            /** @var \AppBundle\Repository\TenantRepository $repo */
            $repo = $em->getRepository('AppBundle:Tenant');
            $libraryId = $this->get('session')->get('account_code');

            if ($tenant = $repo->findOneBy(['stub' => $libraryId])) {
                foreach ($loan->getLoanRows() AS $row) {
                    /** @var $row \AppBundle\Entity\LoanRow */
                    $item = $row->getInventoryItem();

                    $s3_bucket = $this->get('tenant_information')->getS3Bucket();
                    $schema    = $this->get('tenant_information')->getSchema();
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
     * @param Loan $loan
     * @return bool
     */
    private function sendCheckoutConfirmationEmail(Loan $loan)
    {

        $senderName  = $this->get('tenant_information')->getCompanyName();
        $senderEmail = $this->get('tenant_information')->getCompanyEmail();

        // Send email confirmation
        $toEmail = $loan->getContact()->getEmail();

        if ($toEmail) {

            $locale = $loan->getContact()->getLocale();

            try {

                $client = new PostmarkClient($this->getParameter('postmark_api_key'));

                // Save and switch locale for sending the email
                $sessionLocale = $this->get('translator')->getLocale();
                $this->get('translator')->setLocale($locale);

                $message = $this->renderView(
                    'emails/loan_checkout.html.twig',
                    array(
                        'loanRows'    => $loan->getLoanRows()
                    )
                );

                // Send any attachments relating to the items being checked out
                $attachments = [];
                $accountCode = $this->get('tenant_information')->getAccountCode();
                $filePathStub = 'https://s3-us-west-2.amazonaws.com/lend-engine/'.$accountCode.'/files/';

                foreach ($loan->getLoanRows() AS $row) {

                    /** @var $row \AppBundle\Entity\LoanRow */
                    if ( count($row->getInventoryItem()->getFileAttachments()) > 0 ) {
                        foreach ( $row->getInventoryItem()->getFileAttachments() AS $file ) {
                            /** @var $file \AppBundle\Entity\FileAttachment */
                            if ($file->getSendToMemberOnCheckout()) {
                                $filePath = $filePathStub.urlencode($file->getFileName());
                                $attachments[] = PostmarkAttachment::fromFile($filePath, $file->getFileName());
                            }
                        }
                    }
                }

                if (!$subject = $this->get('settings')->getSettingValue('email_loan_confirmation_subject')) {
                    $subject = $this->get('translator')->trans('le_email.checkout.subject', [], 'emails', $locale);
                }

                $client->sendEmail(
                    "{$senderName} <hello@lend-engine.com>",
                    $toEmail,
                    $subject." (Ref ".$loan->getId().")",
                    $message,
                    null,
                    null,
                    null,
                    $senderEmail,
                    null,
                    null,
                    null,
                    $attachments
                );

                // Revert locale for the UI
                $this->get('translator')->setLocale($sessionLocale);

                return true;

            } catch (\Exception $generalException) {

                return false;

            }

        }

        return true;
    }


}
