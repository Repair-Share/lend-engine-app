<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\MembershipType;
use AppBundle\Form\Type\MembershipTypeForm;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MembershipTypeController extends Controller
{
    /**
     * @Route("admin/membershipType/list", name="membershipType_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();
        $subscriptions = $em->getRepository('AppBundle:MembershipType')->findAll();

        $tableHeader = array(
            'Name',
            'Self-serve',
            'Cost',
            'Duration',
            'Discount',
            ''
        );

        foreach ($subscriptions AS $i) {
            /** @var $i \AppBundle\Entity\MembershipType */

            $discount = '';
            if ($i->getDiscount() > 0) {
                $discount = $i->getDiscount().'%';
            }
            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    $i->getIsSelfServe() ? 'Yes' : '',
                    $i->getPrice(),
                    $i->getDuration().' days',
                    $discount,
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('membershipType');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About membership types</h4>
You can create different 'classes' of membership.
At the moment, these classes are mainly used to offer different prices to different groups of members,
using the discount field. Each membership type can have a different duration or price.
<br><br>
A member only has one active membership.
<br>Optionally, you can create ONE 'self serve' membership type which will automatically be assigned to a new member when they register online.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Membership types',
                'pageTitle'  => 'Membership types',
                'addButtonText' => 'Add a membership type',
                'entityName' => 'Membership',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing memberships
     * @Route("admin/membershipType/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="membershipType")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function membershipTypeAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $membership = $this->getDoctrine()
                ->getRepository('AppBundle:MembershipType')
                ->find($id);
            if (!$membership) {
                throw $this->createNotFoundException(
                    'No membership type found for id '.$id
                );
            }
        } else {
            $membership = new MembershipType();
            $user = $this->getUser();
            $membership->setCreatedBy($user);
        }

        $form = $this->createForm(MembershipTypeForm::class, $membership, array(
            'action' => $this->generateUrl('membershipType', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($membership);
            $em->flush();
            $this->addFlash('success', 'Membership type saved.');
            return $this->redirectToRoute('membershipType_list');
        }

        return $this->render(
            'modals/membershipType.html.twig',
            array(
                'title' => 'Membership Type',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }
}