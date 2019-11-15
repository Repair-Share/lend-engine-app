<?php

namespace AppBundle\Controller\Admin\Contact;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ContactListDataController extends Controller
{

    /**
     * JSON responder for DataTables AJAX product list
     * Also see similar usage of contact search on member site in member_search route
     * @Route("admin/dt/contact/list", name="dt_contact_list")
     */
    public function contactDataList(Request $request)
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
        if ($membershipType = $request->get('membershipType')) {
            $filter['membershipType'] = $membershipType;
            $filter['hasMembership'] = 1; // override a blank value
        }

        $sort = [
            'column'    => 'firstName',
            'direction' => 'ASC'
        ];
        if ($sortData = $request->get('order')) {
            $sortByColumnId = $sortData[0]['column']; // assumes single column sort
            $sort['direction'] = $sortData[0]['dir'];
            switch ($sortByColumnId) {
                case 0:
                    $sort['column'] = 'firstName';
                    break;
                case 1:
                    $sort['column'] = 'balance';
                    break;
                case 4:
                    $sort['column'] = 'enabled';
                    break;
            }
        }

        /***** MAIN QUERY ****/
        $searchResults = $contactService->contactSearch($start, $length, $filter, $sort);
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
            $row[] = $nameHtml;

            if ($balance = $contact->getBalance()) {
                if ($balance > 0) {
                    $balance = '<div style="color: #408233;">'.$currencySymbol.' '.number_format($balance,2).'</div>';
                } else if ($balance < 0) {
                    $balance = '<div style="color: #d42d1e;">'.$currencySymbol.' '.number_format($balance,2).'</div>';
                } else {
                    $balance = '<div style="color: #CCC;">'.$currencySymbol.' '.number_format($balance,2).'</div>';
                }
            }
            $row[] = $balance;

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
                    if ($field->getRequired()) {
                        $row[] = '<label class="label bg-red">Missing</label>';
                    } else {
                        $row[] = '';
                    }
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

}