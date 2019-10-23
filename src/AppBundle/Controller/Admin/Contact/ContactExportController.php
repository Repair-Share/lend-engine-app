<?php

namespace AppBundle\Controller\Admin\Contact;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactExportController extends Controller
{

    /**
     * @Route("admin/export/contacts/", name="export_contacts")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportContactsAction(Request $request)
    {

        $container = $this->container;
        $response = new StreamedResponse(function() use($container) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Contact ID',
                'Member ID',
                'Added on',
                'First name',
                'Last name',
                'Membership',
                'Email',
                'Subscriber',
                'Telephone',
                'Street',
                'City',
                'County',
                'Postcode',
                'Country',
                'Balance'
            ];

            /** @var \AppBundle\Repository\ContactFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');

            /** @var \AppBundle\Repository\ContactFieldSelectOptionRepository $fieldOptionRepo */
            $fieldOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ContactFieldSelectOption');

            $customFields = $fieldRepo->findAllOrderedBySort();

            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ContactField */
                $header[] = $field->getName();
            }

            fputcsv($handle, $header);

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Repository\ContactRepository $contactRepo */
            $contactRepo = $em->getRepository('AppBundle:Contact');
            $contacts = $contactRepo->findAll();

            foreach ($contacts AS $contact) {
                /** @var $contact \AppBundle\Entity\Contact */

                $membership = '';
                if ($contact->getActiveMembership()) {
                    $membership = $contact->getActiveMembership()->getMembershipType()->getName();
                }

                if ($contact->getSubscriber()) {
                    $subs = 'Yes';
                } else {
                    $subs = 'No';
                }

                $contactArray = [
                    $contact->getId(),
                    $contact->getMembershipNumber(),
                    $contact->getCreatedAt()->format("Y-m-d"),
                    $contact->getFirstName(),
                    $contact->getLastName(),
                    $membership,
                    $contact->getEmail(),
                    $subs,
                    $contact->getTelephone(),
                    $contact->getAddressLine1(),
                    $contact->getAddressLine2(),
                    $contact->getAddressLine3(),
                    $contact->getAddressLine4(),
                    $contact->getCountryIsoCode(),
                    $contact->getBalance(),
                ];

                $customFieldValues = $contact->getFieldValues();

                foreach ($customFields AS $field) {
                    /** @var $field \AppBundle\Entity\ContactField */
                    $fieldId   = $field->getId();

                    $value = '';
                    if (isset($customFieldValues[$fieldId])) {
                        /** @var \AppBundle\Entity\ContactFieldValue $ContactFieldValue */
                        $ContactFieldValue = $customFieldValues[$fieldId];
                        if ($field->getType() == 'choice' && $optionId = $ContactFieldValue->getFieldValue()) {
                            if ($fieldOptionRepo->find($optionId)) {
                                $value = $fieldOptionRepo->find($optionId)->getOptionName();
                            }
                        } else if ($field->getType() == 'multiselect' && $optionIdString = $ContactFieldValue->getFieldValue()) {
                            $optionIds = explode(',', $optionIdString);
                            $contactFieldSelectOptionNames = [];
                            foreach ($optionIds AS $optionId) {
                                if ($fieldOptionRepo->find($optionId)) {
                                    $contactFieldSelectOptionNames[] = $fieldOptionRepo->find($optionId)->getOptionName();
                                }
                            }
                            $value = implode(',', $contactFieldSelectOptionNames);
                        } else if ($field->getType() == 'checkbox') {
                            if ($ContactFieldValue->getFieldValue() == 1) {
                                $value = 'Yes';
                            }
                        } else {
                            $value = $ContactFieldValue->getFieldValue();
                        }
                    }

                    $contactArray[] = $value;
                }

                fputcsv($handle, $contactArray);

            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="contacts.csv"');

        return $response;

    }

}