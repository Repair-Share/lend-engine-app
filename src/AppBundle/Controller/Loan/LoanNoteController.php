<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LoanNoteController
 * @package AppBundle\Controller
 */
class LoanNoteController extends Controller
{

    /**
     * @param $loanId
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Route("loan/{loanId}/add-note", requirements={"loanId": "\d+"}, name="loan_add_note")
     */
    public function addNote($loanId, Request $request)
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

        if ($noteText = $request->request->get('loanNotes')) {

            $note = new Note();
            $note->setCreatedBy($user);
            $note->setCreatedAt(new \DateTime());
            $note->setLoan($loan);
            $note->setText($noteText);

            $noteIsPublic = $request->request->get('noteIsPublic');
            if ($noteIsPublic) {
                $note->setAdminOnly(false);
            } else {
                $note->setAdminOnly(true);
            }

            $em->persist($note);

            try {
                $em->flush($note);
                $this->addFlash('success', "Added note OK.");
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}