<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SiteMyEventsController extends Controller
{
    /**
     * @Route("member/my-events", name="my_events")
     */
    public function myEventsAction()
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

        return $this->render(
            'member_site/pages/my_events.html.twig',
            [
                'user' => $user,
                'apiKey' => base64_encode(getenv('GOOGLE_MAPS_API_KEY_JS'))
            ]
        );
    }

}