<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoanItemRemoveController extends Controller
{
    /**
     * @param Request $request
     * @param $rowId int
     * @return Response
     * @Route("loan/remove-item/{rowId}", name="loan_item_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loanItemRemoveAction(Request $request, $rowId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRowRepository $loanRowRepo */
        $loanRowRepo = $em->getRepository('AppBundle:LoanRow');

        if (!$loanRow = $loanRowRepo->find($rowId)) {
            $this->addFlash('error', "Could not find loan row {$rowId}");
            return $this->redirectToRoute('home');
        }

        /** @var \AppBundle\Entity\LoanRow $loanRow */
        $loan = $loanRow->getLoan();

        if (!in_array($loan->getStatus(), [Loan::STATUS_PENDING, Loan::STATUS_RESERVED])) {
            $this->addFlash('error', "You can only remove items from Pending loans or Reservations");
            return $this->redirectToRoute('home');
        }

        $loan->removeLoanRow($loanRow);
        $em->remove($loanRow);
        $em->persist($loan);

        try {
            $em->flush();
            $this->addFlash('success', "Item removed");
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);
    }
}
