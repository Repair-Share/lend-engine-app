<?php

namespace AppBundle\Controller\Loan;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LoanSaveController
 * @package AppBundle\Controller
 */
class LoanSaveController extends Controller
{

    /**
     * @param $loanId
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Route("loan/{loanId}/save", requirements={"loanId": "\d+"}, name="loan_save")
     */
    public function saveChangesToLoan($loanId, Request $request)
    {

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        if ($fees = $request->request->get('row_fee')) {

            foreach ($loan->getLoanRows() AS $row) {
                /** @var \AppBundle\Entity\LoanRow $item */
                $item = $row->getInventoryItem();
                $row->setFee($fees[$item->getId()]);
                $em->persist($row);
            }

            try {
                $em->flush();
                $this->addFlash('success', "Saved OK.");
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}