<?php

namespace AppBundle\Controller\Loan;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LoanExportController extends Controller
{

    /**
     * @Route("admin/export/loans/", name="export_loans")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportLoansAction(Request $request)
    {

        $container = $this->container;
        $response = new StreamedResponse(function() use($container) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Loan ID',
                'Status',
                'Added on',
                'First name',
                'Last name',
                'Email',
                'Item name',
                'Item code',
                'Fee',
                'Due out',
                'Checked out',
                'Due in',
                'Checked in',
            ];

            fputcsv($handle, $header);

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Entity\LoanRepository $loanRepo */
            $loanRepo = $em->getRepository('AppBundle:Loan');
            $loans = $loanRepo->findAll();

            foreach ($loans AS $loan) {
                /** @var $loan \AppBundle\Entity\Loan */
                foreach ($loan->getLoanRows() AS $row) {
                    /** @var $row \AppBundle\Entity\LoanRow */

                    if ($row->getDueOutAt()) {
                        $dueOut = $row->getDueOutAt()->format("Y-m-d");
                    } else {
                        $dueOut = '-';
                    }

                    if ($row->getCheckedOutAt()) {
                        $checkedOutAt = $row->getCheckedOutAt()->format("Y-m-d");
                    } else {
                        $checkedOutAt = '-';
                    }

                    if ($row->getDueInAt()) {
                        $dueIn = $row->getDueInAt()->format("Y-m-d");
                    } else {
                        $dueIn = '-';
                    }

                    if ($row->getCheckedInAt()) {
                        $checkedInAt = $row->getCheckedInAt()->format("Y-m-d");
                    } else {
                        $checkedInAt = '-';
                    }

                    $loanArray = [
                        $loan->getId(),
                        $loan->getStatus(),
                        $loan->getCreatedAt()->format("Y-m-d"),
                        $loan->getContact()->getFirstName(),
                        $loan->getContact()->getLastName(),
                        $loan->getContact()->getEmail(),
                        $row->getInventoryItem()->getName(),
                        $row->getInventoryItem()->getSku(),
                        $row->getFee(),
                        $dueOut,
                        $checkedOutAt,
                        $dueIn,
                        $checkedInAt
                    ];

                    fputcsv($handle, $loanArray);
                }
            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="loans.csv"');

        return $response;

    }

}