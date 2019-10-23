<?php

namespace AppBundle\Controller\Admin\Membership;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\MembershipType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MembershipEditController extends Controller
{

    /**
     * @Route("admin/membership/{id}/edit", defaults={"id" = 0}, requirements={"id": "\d+"}, name="membership_edit")
     */
    public function membershipEdit(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $membershipRepo = $em->getRepository('AppBundle:Membership');

        /** @var \AppBundle\Entity\Membership $membership */
        $membership = $membershipRepo->find($id);

        if ($request->get('new_start_date')) {

            $newStartDate = new \DateTime($request->get('new_start_date'));

            $note = new Note();
            $note->setCreatedBy($user);
            $note->setContact($membership->getContact());
            $note->setText("Membership start date changed from <strong>".$membership->getStartsAt()->format("d M Y")."</strong> to <strong>".$newStartDate->format("d M Y")."</strong>");

            $membership->setStartsAt($newStartDate);
            $em->persist($membership);
            $em->persist($note);

            try {
                $em->flush();
                $this->addFlash('success','Membership updated OK.');
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'There was an error editing the membership.');
            }

            return $this->redirectToRoute('contact', array('id' => $membership->getContact()->getId()));
        }

        return $this->render(
            'membership/membership_edit.html.twig',
            array(
                'title' => 'Edit membership',
                'subTitle' => '',
                'membership' => $membership
            )
        );
    }

}