<?php

namespace AppBundle\Controller\Contact;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ContactListController extends Controller
{

    private $filterDateFrom;
    private $filterDateTo;

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/contact/list", name="contact_list")
     */
    public function contactListAction(Request $request)
    {
        $searchString = $request->get('search');

        $this->setDateRange($request);

        if ($this->get('service.tenant')->getFeature('ContactField')) {
            /** @var \AppBundle\Repository\ContactFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');
            $customFields = $fieldRepo->findAllOrderedBySort();
        } else {
            $customFields = [];
        }

        if ($request->get('hasMembership')) {
            $pageTitle = 'Members';
        } else {
            $pageTitle = 'All contacts';
        }

        return $this->render(
            'contact/contact_list.html.twig',
            array(
                'pageTitle'    => $pageTitle,
                'searchString' => $searchString,
                'date_from'    => $this->filterDateFrom,
                'date_to'      => $this->filterDateTo,
                'customFields' => $customFields,
                'type'         => 'all'
            )
        );
    }

    /**
     * JSON responder for DataTables AJAX product list
     * Also see similar usage of contact search on member site in member_search route
     * @Route("admin/dt/contact/list", name="dt_contact_list")
     */
    public function tableListAction(Request $request)
    {
        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $currencySymbol = $this->get('service.tenant')->getCurrencySymbol();

        $data = array();

        $draw = $request->get('draw');

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        if ($this->get('service.tenant')->getFeature('ContactField')) {
            /** @var \AppBundle\Repository\ContactFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');
            $customFields = $fieldRepo->findAllOrderedBySort();
        } else {
            $customFields = [];
        }

        /** @var \AppBundle\Repository\ContactFieldSelectOptionRepository $fieldOptionRepo */
        $fieldOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ContactFieldSelectOption');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        if ($request->get('date_from')) {
            $filter['date_from'] = $request->get('date_from');
        }
        if ($request->get('date_to')) {
            $filter['date_to'] = $request->get('date_to');
        }
        if ($request->get('hasMembership') == 1) {
            $filter['hasMembership'] = 1;
        }

        /***** MAIN QUERY ****/
        $searchResults = $contactService->contactSearch($start, $length, $filter);
        $totalRecords = $searchResults['totalResults'];
        $contacts     = $searchResults['data'];

        foreach ($contacts AS $contact) {

            $row = array();

            /** @var \AppBundle\Entity\Contact $contact */
            $editUrl   = $this->generateUrl('contact', array('id' => $contact->getId()));

            if (!$contact->getFirstName() && !$contact->getLastName()) {
                $contact->setFirstName('- no name -');
            }
            $nameHtml = '<a title="Edit" href="'.$editUrl.'">'.$contact->getFirstName().' '.$contact->getLastName().'</a>';
            if ($contact->getMembershipNumber()) {
                $nameHtml .= '<div style="font-size: 11px; color: #6c6c6c;">Member #'.$contact->getMembershipNumber().'</div>';
            }
            if ($balance = $contact->getBalance()) {
                if ($balance > 0) {
                    $nameHtml .= '<div style="font-size: 11px; color: #408233;">'.$currencySymbol.' '.number_format($balance,2).'</div>';
                } else if ($balance < 0) {
                    $nameHtml .= '<div style="font-size: 11px; color: #d42d1e;">'.$currencySymbol.' '.number_format($balance,2).'</div>';
                }
            }
            $row[] = $nameHtml;

            if ($contact->getActiveMembership()) {
                $memberType = $contact->getActiveMembership()->getMembershipType()->getName();
            } else {
                $memberType = '-';
            }
            $row[] = "{$memberType}";

            if ($contact->isEnabled() == true) {
                $row[] = '';
            } else {
                $row[] = '<i class="fa fa-exclamation-circle" style="color: #ff741e;" data-toggle="tooltip" title="Cannot log in to your member site. Perhaps they have not confirmed their email."></i>';
            }

            $row[] = "{$contact->getEmail()}";
            $row[] = "{$contact->getTelephone()}";

            // Add extra columns for selected custom fields
            $customFieldValues = $contact->getFieldValues();

            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ContactField */
                if ($field->getShowOnContactList() != true) {
                    continue;
                }
                $fieldId   = $field->getId();
                if (isset($customFieldValues[$fieldId])) {
                    /** @var \AppBundle\Entity\ContactFieldValue $contactFieldValue */
                    $contactFieldValue = $customFieldValues[$fieldId];
                    if ($field->getType() == 'choice' && $optionId = $contactFieldValue->getFieldValue()) {
                        $contactFieldSelectOptionName = $fieldOptionRepo->find($optionId)->getOptionName();
                        $row[] = $contactFieldSelectOptionName;
                    } else if ($field->getType() == 'multiselect' && $optionIdString = $contactFieldValue->getFieldValue()) {
                        $optionIds = explode(',', $optionIdString);
                        $contactFieldSelectOptionNames = [];
                        foreach ($optionIds AS $optionId) {
                            $contactFieldSelectOptionNames[] = $fieldOptionRepo->find($optionId)->getOptionName();
                        }
                        $row[] = implode(', ', $contactFieldSelectOptionNames);
                    } else if ($field->getType() == 'checkbox') {
                        if ($contactFieldValue->getFieldValue() == 1) {
                            $row[] = 'Yes';
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = $contactFieldValue->getFieldValue();
                    }
                } else {
                    $row[] = '';
                }
            }

            $data[] = $row;

        }

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $totalRecords,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * Default date ranges for contact list
     * @param Request $request
     */
    private function setDateRange(Request $request)
    {

        if ($request->get('date_from')) {
            $date_from = $request->get('date_from');
        } else {
            $dateFrom = new \DateTime();
            $dateFrom->modify("-5 years");
            $date_from = $dateFrom->format("Y-m-d");
        }

        if ($request->get('date_to')) {
            $date_to = $request->get('date_to');
        } else {
            $dateTo = new \DateTime();
            $dateTo->modify("now");
            $date_to = $dateTo->format("Y-m-d");
        }

        // Set the filters to the same value as the data
        $this->filterDateFrom = $date_from;
        $this->filterDateTo   = $date_to;

    }

}