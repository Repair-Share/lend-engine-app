<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class LoanDeleteController extends Controller
{
    /**
     * @Route("admin/loan/{id}/delete", name="loan_delete", defaults={"id" = 0}, requirements={"id": "\d+"})
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loanDeleteAction($id)
    {

        $em   = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Entity\Loan $loan */
        $loan = $repo->find($id);

        if ($loan->getStatus() == Loan::STATUS_ACTIVE) {
            $this->addFlash('error', "You can't delete active loans.");
            return $this->redirectToRoute('loan_list');
        }

        if ($loan->getStatus() == Loan::STATUS_OVERDUE) {
            $this->addFlash('error', "You can't delete overdue loans.");
            return $this->redirectToRoute('loan_list');
        }

        // Get all item movements associated with the loan (for closed loans)
        /** @var \AppBundle\Repository\ItemMovementRepository $itemMovementRepo */
        $itemMovementRepo = $em->getRepository('AppBundle:ItemMovement');

        /** @var \AppBundle\Repository\DepositRepository $depositRepo */
        $depositRepo = $em->getRepository('AppBundle:Deposit');

        foreach ($loan->getLoanRows() AS $row) {

            $itemMovements = $itemMovementRepo->findBy(['loanRow' => $row->getId()]);
            foreach ($itemMovements AS $movement) {
                $em->remove($movement);
            }

            $deposits = $depositRepo->findBy(['loanRow' => $row->getId()]);
            foreach ($deposits AS $deposit) {
                if ($deposit->getBalance() == 0) {
//                    $em->remove($deposit);
                    // @TODO foreign key dependencies need fixing for deleting deposits
                } else {
                    
                }
                $this->addFlash("error", "You can't currently delete loans with deposits.");
                return $this->redirectToRoute('loan_list');
            }

        }

        $em->remove($loan);

        try {
            $em->flush();
            $this->addFlash('success', 'Loan deleted!');
        } catch(\Exception $generalException) {
            $this->addFlash('error', 'Loan failed to delete.');
            $this->addFlash('debug', $generalException->getMessage());
        }

        return $this->redirectToRoute('loan_list');

    }
}