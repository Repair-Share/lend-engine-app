<?php

namespace AppBundle\Controller\Loan;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanPrintController extends Controller
{

    /**
     * @Route("loan/{id}/print", requirements={"id": "\d+"}, name="loan_print")
     */
    public function printLoan($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRepository $loanRepo */
        $loanRepo = $em->getRepository('AppBundle:Loan');

        if (!$loan = $loanRepo->find($id)){
            $this->addFlash('error', "Loan does not exist");
            return $this->redirectToRoute('home');
        }

        $user = $this->getUser();

        if ($user->hasRole("ROLE_ADMIN") || $user->getId() == $loan->getContact()->getId()) {
            // permission OK
        } else {
            $this->addFlash('error', "You don't have permission to view this loan");
            return $this->redirectToRoute('home');
        }

        return $this->render('member_site/loan/print.html.twig', [
                'loan' => $loan
            ]
        );

    }

}