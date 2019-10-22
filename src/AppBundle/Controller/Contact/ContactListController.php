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
    public function contactList(Request $request)
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

        /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
        $membershipTypeRepo = $this->getDoctrine()->getRepository('AppBundle:MembershipType');
        $membershipTypes = $membershipTypeRepo->findAllOrderedByName(true);

        if ($request->get('hasMembership')) {
            $pageTitle = 'Members';
        } else {
            $pageTitle = 'All contacts';
        }

        return $this->render(
            'contact/contact_list.html.twig',
            [
                'pageTitle'    => $pageTitle,
                'searchString' => $searchString,
                'date_from'    => $this->filterDateFrom,
                'date_to'      => $this->filterDateTo,
                'customFields' => $customFields,
                'membershipTypes' => $membershipTypes,
                'type'         => 'all'
            ]
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