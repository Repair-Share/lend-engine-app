<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductField;
use AppBundle\Form\Type\ProductFieldType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ItemFieldController extends Controller
{

    /**
     * Modal content for managing product fields
     * @Route("admin/productField/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="product_field")
     */
    public function productFieldAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $fieldType = '';

        if ($id) {
            $productField = $this->getDoctrine()->getRepository('AppBundle:ProductField')->find($id);
            if (!$productField) {
                throw $this->createNotFoundException(
                    'No field found for id '.$id
                );
            }
            $fieldType = $productField->getType();
        } else {
            $productField = new ProductField();
        }

        $form = $this->createForm(ProductFieldType::class, $productField, array(
            'action' => $this->generateUrl('product_field', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($id) {
                // Since type is disabled for edit
                $productField->setType($fieldType);
            }
            $em->persist($productField);
            $em->flush();
            $this->addFlash('success', 'Field saved.');
            return $this->redirectToRoute('product_field_list');
        }

        return $this->render(
            'modals/productField.html.twig',
            array(
                'title' => 'Product custom field',
                'subTitle' => '',
                'form' => $form->createView(),
            )
        );

    }

    /**
     * @Route("admin/productField/list", name="product_field_list")
     */
    public function listAction(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();
        $fields = $em->getRepository('AppBundle:ProductField')->findAllOrderedBySort();

        $tableHeader = array(
            'Field',
            'Type',
            'Show on item list',
            '',
            ''
        );

        foreach ($fields AS $i) {

            /** @var \AppBundle\Entity\ProductField $i */
            $fieldType = $i->getType();
            $typeName = '';
            $optionsLink = '';
            switch ($fieldType) {
                case "text":
                    $typeName = 'Text';
                    break;
                case "textarea":
                    $typeName = 'Text area';
                    break;
                case "date":
                    $typeName = 'Date';
                    break;
                case "checkbox":
                    $typeName = 'Check box';
                    break;
                case "multiselect":
                    $typeName = 'Multi-select menu';
                    $url = $this->generateUrl('product_field_select_option_list', array('fieldId' => $i->getId()));
                    $optionsLink = '<a href="'.$url.'">Edit options</a>';
                    break;
                case "choice":
                    $typeName = 'Select menu';
                    $url = $this->generateUrl('product_field_select_option_list', array('fieldId' => $i->getId()));
                    $optionsLink = '<a href="'.$url.'">Edit options</a>';
                    break;
            }

            if ($i->getShowOnItemList()) {
                $show = 'Yes';
            } else {
                $show = '';
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    $typeName,
                    $show,
                    $optionsLink,
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('product_field');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About item custom fields</h4>
These fields are used to extend the information you store about your items.
They can be regular text input boxes, choices, checkboxes, or multi-selects.
<br><br>You can choose whether item custom fields appear on the member site.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Item custom fields',
                'pageTitle'  => 'Item custom fields',
                'entityName' => 'ProductField',
                'addButtonText' => 'Add a custom field',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            )
        );
    }

}