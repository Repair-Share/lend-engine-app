<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\CheckOutPrompt;
use AppBundle\Form\Type\Settings\CheckOutPromptType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CheckOutPromptController extends Controller
{
    /**
     * @Route("admin/checkOutPrompt/list", name="checkOutPrompt_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();
        $prompts = $em->getRepository('AppBundle:CheckOutPrompt')->findAllOrderedBySort();

        $tableHeader = array(
            'Name',
            'On for new items',
            ''
        );

        foreach ($prompts AS $i) {
            /** @var $i \AppBundle\Entity\CheckOutPrompt */
            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    $i->getDefaultOn() ? 'Yes' : '',
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('checkOutPrompt');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About check out prompts</h4>
Use these to remind your staff to do things when they are checking out an item;
such as give a member safety information, or perhaps some extra fluid for a cleaner.
<br><br>
You can have different prompts for each item.
All prompts for all items on the loan must be confirmed before a loan can be checked out.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Check-out prompts',
                'pageTitle'  => 'Check-out prompts',
                'addButtonText' => 'Add a check-out prompt',
                'entityName' => 'CheckOutPrompt', // Used in the sort order handler
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing conditions
     * @Route("admin/checkOutPrompt/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="checkOutPrompt")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function checkOutPromptAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $prompt = $this->getDoctrine()->getRepository('AppBundle:CheckOutPrompt')->find($id);
            if (!$prompt) {
                throw $this->createNotFoundException(
                    'No prompt found for id '.$id
                );
            }
        } else {
            $prompt = new CheckOutPrompt();
        }

        $form = $this->createForm(CheckOutPromptType::class, $prompt, array(
            'action' => $this->generateUrl('checkOutPrompt', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($prompt);

            if ($form->get('setForAllItems')->getData()) {
                $products = $this->getDoctrine()->getRepository('AppBundle:InventoryItem')->findAll();
                foreach ($products AS $product) {
                    /** @var $product \AppBundle\Entity\InventoryItem */
                    $product->addCheckOutPrompt($prompt);
                    $em->persist($product);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Prompt saved.');

            return $this->redirectToRoute('checkOutPrompt_list');
        }

        return $this->render(
            'modals/settings/checkOutPrompt.html.twig',
            array(
                'title' => 'Check-out prompt',
                'subTitle' => '',
                'form' => $form->createView(),
                'prompt' => $prompt
            )
        );

    }
}