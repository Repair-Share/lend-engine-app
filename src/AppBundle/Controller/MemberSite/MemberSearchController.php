<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class MemberSearchController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("member-search", name="member_search")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function memberSearchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $user = $this->getUser();

        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $user->getId() != $sessionUserId) {
            // Get the member
            $user = $contactRepo->find($sessionUserId);
        }

        $contacts = [];

        if ($string = $request->get('member-search')) {
            $filter = [
                'search' => $string
            ];
            $searchResults = $contactService->contactSearch(0, 50, $filter);

            foreach ($searchResults['data'] AS $contact) {
                /** @var $contact \AppBundle\Entity\Contact */
                $contacts[] = $contact;
            }
        }

        return $this->render('member_site/pages/member_search.html.twig', [
            'user'     => $user,
            'contacts' => $contacts
        ]);
    }

}