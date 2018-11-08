<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductField;
use AppBundle\Entity\ProductFieldSelectOption;
use AppBundle\Form\Type\ProductFieldSelectOptionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ItemFieldSelectOptionController extends Controller
{
    // @todo update this class to use ITEM instead of PRODUCT

    /**
     * Modal content for managing product fields
     * @Route("admin/productFieldSelectOption/{fieldId}/{id}", defaults={"id" = 0}, requirements={"fieldId": "\d+", "id": "\d+"}, name="product_field_select_option")
     */
    public function productFieldSelectOptionAction(Request $request, $id, $fieldId)
    {
        $em = $this->getDoctrine()->getManager();

        $productFieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
        $productField = $productFieldRepo->find($fieldId);

        $subTitle = '';

        if ($id) {

            $productFieldSelectOption = $this->getDoctrine()
                ->getRepository('AppBundle:ProductFieldSelectOption')
                ->find($id);
            if (!$productFieldSelectOption) {
                throw $this->createNotFoundException(
                    'No select list option found for id '.$id
                );
            }
            $modalTitle = 'Edit choice';

        } else {
            $productFieldSelectOption = new ProductFieldSelectOption();
            $modalTitle = 'Add a choice for '.$productField->getName();
        }

        $form = $this->createForm(ProductFieldSelectOptionType::class, $productFieldSelectOption, array(
            'action' => $this->generateUrl('product_field_select_option', array('fieldId' => $fieldId, 'id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $productFieldSelectOption->setProductField($productField);

            $em->persist($productFieldSelectOption);

            $em->flush();
            $this->addFlash('success', 'Option saved.');

            return $this->redirectToRoute('product_field_select_option_list', array('fieldId' => $fieldId));
        }

        return $this->render(
            'modals/productFieldSelectOption.html.twig',
            array(
                'title' => $modalTitle,
                'subTitle' => $subTitle,
                'form' => $form->createView()
            )
        );

    }

    /**
     * @Route("admin/productFieldSelectOption/list/{fieldId}", defaults={"fieldId" = 0}, name="product_field_select_option_list")
     */
    public function listAction($fieldId)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        $productFieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
        $productField = $productFieldRepo->find($fieldId);

        $selectOptions = $em->getRepository('AppBundle:ProductFieldSelectOption')->findAllOrderedBySort($fieldId);

        $tableHeader = array(
            'Option',
            ''
        );

        foreach ($selectOptions AS $i) {
            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getOptionName(),
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('product_field_select_option', array('fieldId' => $fieldId));

        $listCustomFieldsPath = $this->generateUrl('product_field_list');

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Choices for field "'.$productField->getName().'"',
                'pageTitle'  => 'Choices for field "'.$productField->getName().'"',
                'entityName' => 'ProductFieldSelectOption',
                'addButtonText' => 'Add an option',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'secondaryMenu' => '<a class="" style="padding-right:10px;" href="'.$listCustomFieldsPath.'">Back to product fields</a>'
            )
        );
    }

}