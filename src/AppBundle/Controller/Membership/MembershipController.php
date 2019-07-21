<?php

namespace AppBundle\Controller\Membership;

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

class MembershipController extends Controller
{
    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/membership/list", name="membership_list")
     */
    public function listAction(Request $request)
    {
        $searchString = $request->get('search');
        return $this->render(
            'membership/membership_list.html.twig',
            array(
                'searchString' => $searchString
            )
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

        $subscriptions = $repo->search($start, $length, $searchString);
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

}