<?php

namespace AppBundle\Controller\Admin\Payment;

use AppBundle\Entity\Payment;
use AppBundle\Entity\PaymentMethod;
use AppBundle\Form\Type\RefundType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class RefundController extends Controller
{

    /**
     * @Route("admin/refund", name="refund")
     */
    public function refundAction(Request $request)
    {
        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $paymentID = $request->get('id');
        $loanRowId = $request->get('goToCheckInItem');

        $filters = ['id' => $paymentID];
        $payments = $paymentService->get($filters);

        $p = null;
        if (count($payments) == 1) {
            /** @var \AppBundle\Entity\Payment $p */
            $p = $payments[0];
        }

        $form = $this->createForm(RefundType::class, null, [
            'em' => $this->getDoctrine()->getManager(),
            'action' => $this->generateUrl('refund', [
                'id' => $paymentID,
                'goToCheckInItem' => $loanRowId
            ])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $chargeID = $p->getPspCode();
            $originalAmount   = $p->getAmount();
            $amount   = $form->get('amount')->getData();

            if ($amount > $originalAmount) {
                $this->addFlash("error", "You can't refund more than the original payment amount.");
                return $this->redirectToRoute("payments");
            }

            $note     = $form->get('note')->getData();

            $refund = new Payment();
            $refund->setType(Payment::PAYMENT_TYPE_REFUND);
            $refund->setAmount($amount);
            $refund->setCreatedBy($this->getUser());
            $refund->setContact($p->getContact());
            $refund->setPaymentMethod($p->getPaymentMethod());

            if ($p->getEvent()) {
                $refund->setEvent($p->getEvent());
            }

            if ($deposit = $p->getDeposit()) {
                $refund->setDeposit($deposit);
                $refund->setNote("Deposit refunded. ".$note);
            } else {
                $refund->setNote("Refund. ".$note);
            }

            // Create it (including talking to the PSP if relevant)
            if ($paymentService->refund($refund, $chargeID)) {
                $this->addFlash('success', "Refunded {$chargeID} OK");

                // Debit account with the refund
                if ($form->get('debitAccount')->getData()) {

                    $em = $this->getDoctrine()->getManager();

                    $debitAccount = $em->getRepository("AppBundle:PaymentMethod")->findOneBy(['name' => PaymentMethod::PAYMENT_METHOD_DEBIT_ACCOUNT]);

                    // Create the debit account if it doesn't exist yet
                    if (!$debitAccount) {

                        $debitAccount = new PaymentMethod();
                        $debitAccount->setName(PaymentMethod::PAYMENT_METHOD_DEBIT_ACCOUNT);
                        $debitAccount->setIsActive(false);

                        $em->persist($debitAccount);
                        $em->flush($debitAccount);

                    }

                    $debit = new Payment();
                    $debit->setType(Payment::PAYMENT_TYPE_PAYMENT);
                    $debit->setAmount($amount);
                    $debit->setCreatedBy($this->getUser());
                    $debit->setContact($p->getContact());
                    $debit->setPaymentMethod($debitAccount);
                    $debit->setNote('Debit account with the refund');
                    $debit->setLoan($p->getLoan());

                    $em->persist($debit);
                    $em->flush($debit);

                }

            } else {
                foreach ($paymentService->errors AS $e) {
                    $this->addFlash('error', $e);
                }
            }

            $contactService->recalculateBalance($refund->getContact());

            return $this->returnUser($loanRowId, $p->getContact()->getId());

        }

        return $this->render('modals/payment_refund.html.twig',
            [
                'form'    => $form->createView(),
                'payment' => $p
            ]
        );
    }

    /**
     * @param null $loanRowId
     * @param null $contactID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function returnUser($loanRowId = null, $contactID = null)
    {
        if ($loanRowId) {
            return $this->redirectToRoute('loan_check_in', ['loanRowId' => $loanRowId]);
        } else {
            return $this->redirectToRoute('contact', ['id' => $contactID]);
        }
    }

}