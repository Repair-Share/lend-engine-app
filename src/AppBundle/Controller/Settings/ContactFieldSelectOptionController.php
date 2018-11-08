<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ContactField;
use AppBundle\Entity\ContactFieldSelectOption;
use AppBundle\Form\Type\ContactFieldSelectOptionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ContactFieldSelectOptionController extends Controller
{

    /**
     * Modal content for managing contact fields
     * @Route("admin/contactFieldSelectOption/{fieldId}/{id}", defaults={"id" = 0}, requirements={"fieldId": "\d+", "id": "\d+"}, name="contact_field_select_option")
     */
    public function contactFieldSelectOptionAction(Request $request, $id, $fieldId)
    {
        $em = $this->getDoctrine()->getManager();

        $contactFieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');
        $contactField = $contactFieldRepo->find($fieldId);

        if ($id) {
            $contactFieldSelectOption = $this->getDoctrine()
                ->getRepository('AppBundle:ContactFieldSelectOption')
                ->find($id);
            if (!$contactFieldSelectOption) {
                throw $this->createNotFoundException(
                    'No select list option found for id '.$id
                );
            }
            $modalTitle = 'Edit choice';
        } else {
            $contactFieldSelectOption = new ContactFieldSelectOption();
            $modalTitle = 'Add a choice for '.$contactField->getName();
        }

        $form = $this->createForm(ContactFieldSelectOptionType::class, $contactFieldSelectOption, array(
            'action' => $this->generateUrl('contact_field_select_option', array('fieldId' => $fieldId, 'id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $contactFieldSelectOption->setContactField($contactField);

            $em->persist($contactFieldSelectOption);

            $em->flush();
            $this->addFlash('success', 'Option saved.');

            return $this->redirectToRoute('contact_field_select_option_list', array('fieldId' => $fieldId));
        }

        return $this->render(
            'modals/contactFieldSelectOption.html.twig',
            array(
                'title' => $modalTitle,
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }

    /**
     * @Route("admin/contactFieldSelectOption/list/{fieldId}", defaults={"fieldId" = 0}, name="contact_field_select_option_list")
     */
    public function listAction($fieldId)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        $contactFieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');
        $contactField = $contactFieldRepo->find($fieldId);

        $selectOptions = $em->getRepository('AppBundle:ContactFieldSelectOption')->findAllOrderedBySort($fieldId);

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

        $modalUrl = $this->generateUrl('contact_field_select_option', array('fieldId' => $fieldId));

        $listCustomFieldsPath = $this->generateUrl('contact_field_list');

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Choices for field "'.$contactField->getName().'"',
                'pageTitle'  => 'Choices for field "'.$contactField->getName().'"',
                'entityName' => 'ContactFieldSelectOption',
                'addButtonText' => 'Add an option',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'secondaryMenu' => '<a class="" style="padding-right:10px;" href="'.$listCustomFieldsPath.'">Back to contact fields</a>'
            )
        );
    }

}