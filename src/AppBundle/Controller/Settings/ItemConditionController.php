<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ItemCondition;
use AppBundle\Form\Type\Settings\ItemConditionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ItemConditionController extends Controller
{
    /**
     * @Route("admin/itemCondition/list", name="itemCondition_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();
        $conditions = $em->getRepository('AppBundle:ItemCondition')->findAllOrderedBySort();

        $tableHeader = array(
            'Name',
            ''
        );

        foreach ($conditions AS $i) {
            /** @var $i \AppBundle\Entity\ItemCondition */
            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('itemCondition');

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Item conditions',
                'pageTitle'  => 'Item conditions',
                'addButtonText' => 'Add a condition',
                'entityName' => 'ItemCondition', // Used in the sort order handler
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true
            )
        );
    }

    /**
     * Modal content for managing conditions
     * @Route("admin/itemCondition/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="itemCondition")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function itemConditionAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $condition = $this->getDoctrine()
                ->getRepository('AppBundle:ItemCondition')
                ->find($id);
            if (!$condition) {
                throw $this->createNotFoundException(
                    'No condition type found for id '.$id
                );
            }
        } else {
            $condition = new ItemCondition();
        }

        $form = $this->createForm(ItemConditionType::class, $condition, array(
            'action' => $this->generateUrl('itemCondition', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($condition);
            $em->flush();
            $this->addFlash('success', 'Condition saved.');
            return $this->redirectToRoute('itemCondition_list');
        }

        return $this->render(
            'modals/settings/itemCondition.html.twig',
            array(
                'title' => 'Item condition',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }
}