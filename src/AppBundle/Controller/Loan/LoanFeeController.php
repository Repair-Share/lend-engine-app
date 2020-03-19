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

        /** @var \AppBundle\Services\Loan\LoanService $loanService */
        $loanService = $this->get('service.loan');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        $feeAmount = $request->request->get('feeAmount');
        $noteText = $request->request->get('feeReason');

        if ($feeAmount && $noteText) {
            if ($loanService->addFee($loan, $user, $feeAmount, $noteText)) {
                $contactService->recalculateBalance($loan->getContact());
                $this->addFlash('success', "Added fee OK.");
            } else {
                foreach ($loanService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
            }
        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}