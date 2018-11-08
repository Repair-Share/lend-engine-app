<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles all the pages on the static marketing site
 * Class SiteController
 * @package AppBundle\Controller
 */
class MemberSiteController extends Controller
{

    /**
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
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

        return $this->render('public/base.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("welcome", name="home-content")
     */
    public function homeAction(Request $request)
    {
        return $this->render('public/pages/home.html.twig', ['testContent' => '']);
    }

    /**
     * @Route("help/waiting-list", name="help-waiting-list")
     */
    public function helpWaitingListAction(Request $request)
    {
        return $this->render('public/pages/waiting_list.html.twig', []);
    }

    /**
     * For test development of a new look and feel
     * @Route("template/", name="template")
     */
    public function testAction(Request $request)
    {
        return $this->render('public/template.html.twig', []);
    }

}
