<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LoanFeeController
 * @package AppBundle\Controller
 */
class LoanFeeController extends Controller
{

    /**
     * @param $loanId
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Route("loan/{loanId}/add-fee", requirements={"loanId": "\d+"}, name="loan_add_fee")
     */
    public function addFee($loanId, Request $request)
    {
        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $currencySymbol = $this->get('tenant_information')->getCurrencySymbol();

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        $feeAmount = $request->request->get('feeAmount');
        $noteText = $request->request->get('feeReason');

        if ($feeAmount && $noteText) {

            $payment = new Payment();
            $payment->setCreatedBy($user);
            $payment->setCreatedAt(new \DateTime());
            $payment->setLoan($loan);
            $payment->setNote($noteText);
            $payment->setContact($loan->getContact());
            $payment->setAmount(-$feeAmount);
            $em->persist($payment);

            // Add audit trail
            $note = new Note();
            $note->setCreatedBy($user);
            $note->setCreatedAt(new \DateTime());
            $note->setContact($loan->getContact());
            $note->setLoan($loan);
            $note->setText("Added fee of {$currencySymbol}" . $payment->getAmount() . "; {$noteText}");
            $em->persist($note);

            try {
                $em->flush();
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

            $contactService->recalculateBalance($loan->getContact());

            $this->addFlash('success', "Added fee OK.");

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}