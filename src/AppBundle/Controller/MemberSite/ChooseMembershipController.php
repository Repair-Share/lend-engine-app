<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChooseMembershipController extends Controller
{

    /**
     * @Route("choose_membership", name="choose_membership")
     * @param Request $request
     * @return Response
     */
    public function chooseMembership(Request $request)
    {

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please ensure you are logged in.");
            return $this->redirectToRoute('homepage');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Entity\Contact $contact */
        if ($contactId = $request->get('c')) {
            if (!$contact = $contactService->get($contactId)) {
                $this->addFlash('error', "Can't find that contact");
                return $this->redirectToRoute('homepage');
            }
        } else {
            $contact = $this->getUser();
        }

        /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
        $membershipTypeRepo = $em->getRepository('AppBundle:MembershipType');

        // Get the available self serve memberships to give to the member as choices
        if (!$user->hasRole("ROLE_ADMIN")) {
            $filter = ['isSelfServe' => true, 'isActive' => true];
        } else {
            $filter = ['isActive' => true];
        }
        $availableMembershipTypes = $membershipTypeRepo->findBy($filter);

        return $this->render(
            'member_site/pages/choose_membership.html.twig',
            [
                'user'    => $contact,
                'itemId'  => $request->get('itemId'),
                'contact' => $contact,
                'membershipTypes' => $availableMembershipTypes
            ]
        );
    }

}
