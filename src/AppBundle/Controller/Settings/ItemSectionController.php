<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductSection;
use AppBundle\Entity\ProductTag;
use AppBundle\Form\Type\Settings\ItemCategoryType;
use AppBundle\Form\Type\Settings\ItemSectionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ItemSectionController extends Controller
{

    /**
     * Modal content for managing sections
     * @Route("admin/section/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="item_section")
     */
    public function sectionCreateOrEdit(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $section = $this->getDoctrine()
                ->getRepository('AppBundle:ProductSection')
                ->find($id);
            if (!$section) {
                throw $this->createNotFoundException(
                    'No section found for id '.$id
                );
            }
        } else {
            $section = new ProductSection();
        }

        $formOptions = [
            'action' => $this->generateUrl('item_section', array('id' => $id))
        ];
        $form = $this->createForm(ItemSectionType::class, $section, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($section);
            $em->flush();
            $this->addFlash('success', 'Section saved.');
            return $this->redirectToRoute('section_list');

        }

        return $this->render(
            'modals/settings/item_section.html.twig',
            array(
                'title' => 'Item section',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }

}