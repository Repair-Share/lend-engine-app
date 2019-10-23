<?php

namespace AppBundle\Controller\Admin\Membership;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MembershipExtendController extends Controller
{

    /**
     * @Route("admin/membership/{id}/extend", defaults={"id" = 0}, requirements={"id": "\d+"}, name="membership_extend")
     */
    public function membershipExtend(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $membershipRepo = $em->getRepository('AppBundle:Membership');

        /** @var \AppBundle\Entity\Membership $membership */
        $membership = $membershipRepo->find($id);

        if ($request->get('new_expiry_date')) {

            $newExpiryDate = new \DateTime($request->get('new_expiry_date'));

            $note = new Note();
            $note->setCreatedBy($user);
            $note->setContact($membership->getContact());
            $note->setText("Membership extended from <strong>".$membership->getExpiresAt()->format("d M Y")."</strong> to <strong>".$newExpiryDate->format("d M Y")."</strong>");

            $membership->setExpiresAt($newExpiryDate);
            $em->persist($membership);
            $em->persist($note);

            try {
                $em->flush();
                $this->addFlash('success','Membership extended.');
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'There was an error extending the membership.');
                $this->addFlash('debug', 'PaymentError:'.$generalException->getMessage());
            }

            return $this->redirectToRoute('contact', array('id' => $membership->getContact()->getId()));
        }

        return $this->render(
            'membership/membership_extend.html.twig',
            array(
                'title' => 'Extend membership',
                'subTitle' => '',
                'membership' => $membership
            )
        );
    }

}