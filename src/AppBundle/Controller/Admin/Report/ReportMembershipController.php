<?php

namespace AppBundle\Controller\Admin\Report;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\MembershipType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ReportMembershipController extends Controller
{
    protected $dateFrom;

    protected $dateTo;

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/report/report_memberships", name="membership_list")
     */
    public function membershipReport(Request $request)
    {
        $searchString = $request->get('search');

        $this->setDateRange($request);

        return $this->render(
            'membership/membership_list.html.twig',
            [
                'searchString' => $searchString,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'memberType' => $request->get('memberType')
            ]
        );
    }

    /**
     * @Route("admin/dt/membership/list", name="dt_membership_list")
     */
    public function tableListAction(Request $request)
    {
        $data = array();

        // Get from the DB
        $em = $this->getDoctrine()->getManager();

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        /** @var \AppBundle\Repository\MembershipRepository $repo */
        $repo = $em->getRepository('AppBundle:Membership');

        // Set date range
        $this->setDateRange($request);

        // Set up filters
        $filter = [
            'search'    => $searchString,
            'memberType' => $request->get('memberType'),
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ];

        $subscriptions = $repo->search($start, $length, $filter);
        $totalRecords  = $repo->countAll();

        foreach ($subscriptions AS $i) {
            /** @var $i \AppBundle\Entity\Membership */

            $action = '';
            if ($i->getStatus() == Membership::SUBS_STATUS_ACTIVE) {
                $status = '<span class="label bg-green">ACTIVE</span>';
                $cancelUrl = $this->generateUrl('membership_cancel', array('id' => $i->getId()));
                $action = '<a href="' . $cancelUrl . '">Cancel</a>';
            } else if ($i->getStatus() == Membership::SUBS_STATUS_EXPIRED) {
                $status = '<span class="label bg-orange">EXPIRED</span>';
            } else {
                $status = '<span class="label bg-gray">CANCELLED</span>';
            }

            $contactUrl   = $this->generateUrl('contact', array('id' => $i->getContact()->getId()));

            $data[] = array(
                '<a href="'.$contactUrl.'">'.$i->getContact()->getFirstName().' '.$i->getContact()->getLastName().'</a>',
                $i->getMembershipType()->getName(),
                $i->getCreatedAt()->format("d M Y"),
                $i->getStartsAt()->format("d M Y"),
                $i->getExpiresAt()->format("d M Y"),
                $status,
                number_format($i->getPrice(), 2),
                $action
            );
        }

        if ($searchString) {
            $count = count($data);
        } else {
            $count = $totalRecords;
        }

        $draw = $request->get('draw');

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $count,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }
    /**
     * @param Request $request
     */
    private function setDateRange(Request $request)
    {
        if ($request->get('date_from')) {
            $date_from = $request->get('date_from');
        } else {
            $dateFrom = new \DateTime("-1 year");
            $date_from = $dateFrom->format("Y-m-d");
        }
        if ($request->get('date_to')) {
            $date_to = $request->get('date_to');
        } else {
            $dateTo = new \DateTime("+1 year");
            $date_to = $dateTo->format("Y-m-d");
        }

        $this->dateFrom = $date_from;
        $this->dateTo = $date_to;
    }

}