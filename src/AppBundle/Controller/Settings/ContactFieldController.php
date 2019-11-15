<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ContactField;
use AppBundle\Form\Type\ContactFieldType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ContactFieldController extends Controller
{

    /**
     * Modal content for managing contact fields
     * @Route("admin/contactField/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="contact_field")
     */
    public function contactFieldAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $fieldType = '';

        if ($id) {
            $contactField = $this->getDoctrine()->getRepository('AppBundle:ContactField')->find($id);
            if (!$contactField) {
                throw $this->createNotFoundException(
                    'No field found for id '.$id
                );
            }
            $fieldType = $contactField->getType();
        } else {
            $contactField = new ContactField();
        }

        $form = $this->createForm(ContactFieldType::class, $contactField, array(
            'action' => $this->generateUrl('contact_field', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($id) {
                // Since type is disabled for edit
                $contactField->setType($fieldType);
            }
            $em->persist($contactField);
            $em->flush();
            $this->addFlash('success', 'Field saved.');
            return $this->redirectToRoute('contact_field_list');
        }

        return $this->render(
            'modals/contactField.html.twig',
            [
                'title' => 'Contact custom field',
                'subTitle' => '',
                'form' => $form->createView()
            ]
        );

    }

    /**
     * @Route("admin/contactField/list", name="contact_field_list")
     */
    public function listAction(Request $request)
    {
        $tableRows = [];
        $em   = $this->getDoctrine()->getManager();
        $fields = $em->getRepository('AppBundle:ContactField')->findAllOrderedBySort();

        $tableHeader = [
            'Field',
            'Type',
            'Show on contact list',
            'Value required',
            '',
            ''
        ];

        foreach ($fields AS $i) {

            /** @var \AppBundle\Entity\ContactField $i */
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
                case "checkbox":
                    $typeName = 'Check box';
                    break;
                case "multiselect":
                    $typeName = 'Multi-select menu';
                    $url = $this->generateUrl('contact_field_select_option_list', array('fieldId' => $i->getId()));
                    $optionsLink = '<a href="'.$url.'">Edit options</a>';
                    break;
                case "choice":
                    $typeName = 'Select menu';
                    $url = $this->generateUrl('contact_field_select_option_list', array('fieldId' => $i->getId()));
                    $optionsLink = '<a href="'.$url.'">Edit options</a>';
                    break;
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getName(),
                    $typeName,
                    $i->getShowOnContactList() == true ? 'Yes' : '',
                    $i->getRequired() == true ? 'Yes' : '',
                    $optionsLink,
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('contact_field');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About contact custom fields</h4>
These fields are used to extend the information you store about your contacts and members.
They can be regular text input boxes, choices, checkboxes, or multi-selects.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Contact custom fields',
                'pageTitle'  => 'Contact custom fields',
                'entityName' => 'ContactField',
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