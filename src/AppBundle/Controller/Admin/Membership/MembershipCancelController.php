<?php

namespace AppBundle\Controller\Admin\Membership;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MembershipCancelController extends Controller
{
    /**
     * @Route("admin/membership/{id}/cancel", defaults={"id" = 0}, requirements={"id": "\d+"}, name="membership_cancel")
     */
    public function membershipCancel(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $membershipRepo = $em->getRepository('AppBundle:Membership');

        /** @var \AppBundle\Entity\Membership $membership */
        $membership = $membershipRepo->find($id);
        $membership->setStatus(Membership::SUBS_STATUS_CANCELLED);
        $em->persist($membership);

        /** @var \AppBundle\Entity\Contact $contact */
        $contact = $membership->getContact();
        $contact->setActiveMembership(null);
        $em->persist($contact);

        try {
            $em->flush();
            $this->addFlash('success', 'Membership cancelled.');
        } catch (\Exception $generalException) {
            $this->addFlash('error', $generalException->getMessage());
        }

        return $this->redirectToRoute('contact', array('id' => $membership->getContact()->getId()));
    }
}