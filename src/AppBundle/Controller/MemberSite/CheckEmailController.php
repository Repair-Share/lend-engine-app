<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckEmailController
 * @package AppBundle\Controller\MemberSite
 */
class CheckEmailController extends Controller
{
    /**
     *
     * The page the user sees when they fill out the registration form and email-activation is enabled
     *
     * @param Request $request
     * @return Response
     * @Route("/check-email", name="check_email")
     */
    public function checkEmail(Request $request)
    {
        return $this->render('member_site/pages/check_email.html.twig', []);
    }
}