<?php

namespace AppBundle\Controller\Admin\Membership;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipExportController extends Controller
{

    /**
     * @Route("admin/export/memberships/", name="export_memberships")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportMembershipsAction(Request $request)
    {

        $container = $this->container;
        $response = new StreamedResponse(function() use($container) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Member ID',
                'First name',
                'Last name',
                'Email',
                'Membership number',
                'Membership type',
                'Starts',
                'Expires',
                'Status',
                'Price',
            ];

            fputcsv($handle, $header);

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Repository\MembershipRepository $membershipRepo */
            $membershipRepo = $em->getRepository('AppBundle:Membership');
            $memberships = $membershipRepo->findAll();

            foreach ($memberships AS $membership) {
                /** @var $membership \AppBundle\Entity\Membership */

                $contact = $membership->getContact();

                $membershipArray = [
                    $contact->getId(),
                    $contact->getFirstName(),
                    $contact->getLastName(),
                    $contact->getEmail(),
                    $contact->getMembershipNumber(),
                    $membership->getMembershipType()->getName(),
                    $membership->getStartsAt()->format("Y-m-d"),
                    $membership->getExpiresAt()->format("Y-m-d"),
                    $membership->getStatus(),
                    $membership->getPrice()
                ];

                fputcsv($handle, $membershipArray);

            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="memberships.csv"');

        return $response;

    }

}