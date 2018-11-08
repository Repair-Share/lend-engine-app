<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Helpers\InputHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Postmark\Models\PostmarkAttachment;
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

        if ($loan->getStatus() == Loan::STATUS_CLOSED) {
            $this->addFlash('error', "You can't delete closed loans.");
            return $this->redirectToRoute('loan_list');
        }

        $em->remove($loan);

        try {
            $em->flush();
            $this->addFlash('success', 'Loan deleted!');
        } catch(\Exception $generalException) {
            $this->addFlash('error', 'Loan failed to delete.');
            $this->addFlash('debug', $generalException->getMessage());
        }

        return $this->redirectToRoute('home');

    }
}