<?php

namespace AppBundle\Controller\Loan;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoanListController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("member/loans", name="loans")
     */
    public function userLoans(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        $user = $this->getUser();

        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $user->getId() != $sessionUserId) {
            // Get the member
            $user = $contactRepo->find($sessionUserId);
        }

        return $this->render('member_site/loan/loans.html.twig', array(
            'user' => $user
        ));
    }
}
