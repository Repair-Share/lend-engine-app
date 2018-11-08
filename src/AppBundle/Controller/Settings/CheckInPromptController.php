<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\CheckInPrompt;
use AppBundle\Form\Type\Settings\CheckInPromptType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class CheckInPromptController extends Controller
{
    /**
     * @Route("admin/checkInPrompt/list", name="checkInPrompt_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();
        $prompts = $em->getRepository('AppBundle:CheckInPrompt')->findAllOrderedBySort();

        $tableHeader = array(
            'Name',
            'On for new items',
            ''
        );

        foreach ($prompts AS $i) {
            /** @var $i \AppBundle\Entity\CheckInPrompt */
            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    $i->getDefaultOn() ? 'Yes' : '',
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('checkInPrompt');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About check in prompts</h4>
Use these to remind your staff to do things when they are checking an item back in;
such as to ensure it's clean, or contains a specific part.
<br><br>
You can have different prompts for each item.
All prompts for an item must be confirmed before the item can be checked back in.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Check-in prompts',
                'pageTitle'  => 'Check-in prompts',
                'addButtonText' => 'Add a check-in prompt',
                'entityName' => 'CheckInPrompt', // Used in the sort order handler
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing check in prompts
     * @Route("admin/checkInPrompt/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="checkInPrompt")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function checkInPromptAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $prompt = $this->getDoctrine()->getRepository('AppBundle:CheckInPrompt')->find($id);
            if (!$prompt) {
                throw $this->createNotFoundException(
                    'No prompt found for id '.$id
                );
            }
        } else {
            $prompt = new CheckInPrompt();
        }

        $form = $this->createForm(CheckInPromptType::class, $prompt, array(
            'action' => $this->generateUrl('checkInPrompt', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($prompt);

            if ($form->get('setForAllItems')->getData()) {
                $products = $this->getDoctrine()->getRepository('AppBundle:InventoryItem')->findAll();
                foreach ($products AS $product) {
                    /** @var $product \AppBundle\Entity\InventoryItem */
                    $product->addCheckInPrompt($prompt);
                    $em->persist($product);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Prompt saved.');

            return $this->redirectToRoute('checkInPrompt_list');
        }

        return $this->render(
            'modals/settings/checkInPrompt.html.twig',
            array(
                'title' => 'Check-in prompt',
                'subTitle' => '',
                'form' => $form->createView(),
                'prompt' => $prompt
            )
        );

    }
}