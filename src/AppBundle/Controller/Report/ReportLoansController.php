<?php

namespace AppBundle\Controller\Report;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportLoansController extends Controller
{

    protected $dateFrom;

    protected $dateTo;

    private $loanStatuses;

    public function __construct()
    {
        $statusArray = array();
        $statusArray[] = array('id' => Loan::STATUS_PENDING, 'name' => 'Pending');
        $statusArray[] = array('id' => Loan::STATUS_ACTIVE, 'name' => 'Active');
        $statusArray[] = array('id' => Loan::STATUS_OVERDUE, 'name' => 'Overdue');
        $statusArray[] = array('id' => Loan::STATUS_CLOSED, 'name' => 'Closed');

        $this->loanStatuses = $statusArray;
    }


    /**
     * @Route("admin/report/report_loans", name="report_loans")
     */
    public function LoansReport(Request $request)
    {
        $tableRows = array();

        $em = $this->getDoctrine()->getManager();
        $membershipTypes = $em->getRepository('AppBundle:MembershipType')->findAllOrderedByName();

        /** @var \AppBundle\Entity\ContactFieldRepository $fieldRepo */
        $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');
        $customFields = $fieldRepo->findAllOrderedBySort();

        /** @var \AppBundle\Entity\ContactFieldSelectOptionRepository $fieldSelectOptionRepo */
        $fieldSelectOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ContactFieldSelectOption');

        $tableHeader = array(
            '',
            'Number of loans'
        );

        /** @var \AppBundle\Services\Report\ReportLoans $report */
        $report = $this->get('report.loans');
        $this->setDateRange($request, $report);

        // Set up filters
        $filter = array(
            'search' => $request->get('search'),
            'group_by' => $request->get('group_by'),
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'statuses' => $request->get('statuses')
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {

            if (!$reportRow['group_name']) {
                $reportRow['group_name'] = '- not set -';
            } else {
                if (isset($reportRow['field_type']) && $reportRow['field_type'] == 'choice') {
                    $option = $fieldSelectOptionRepo->find($reportRow['group_name']);
                    $reportRow['group_name'] = $option->getOptionName();
                } else if (isset($reportRow['field_type']) && $reportRow['field_type'] == 'checkbox') {
                    $reportRow['group_name'] = $this->valueToYesNo($reportRow['group_name']);
                }
            }

            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $reportRow['group_name'],
                    $reportRow['qty']
                )
            );
            $n++;
        }

        return $this->render('report/report_loans.html.twig', array(
            'pageTitle' => 'Loans by item or member',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows,
            'customFields' => $customFields,
            'statuses' => $this->loanStatuses,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ));
    }

    /**
     * @param Request $request
     */
    private function setDateRange(Request $request)
    {
        if ($request->get('date_from')) {
            $date_from = $request->get('date_from');
        } else {
            $dateFrom = new \DateTime();
            $date_from_year = $dateFrom->format("Y");
            $date_from = $date_from_year.'-01-01';
        }
        if ($request->get('date_to')) {
            $date_to = $request->get('date_to');
        } else {
            $dateTo = new \DateTime();
            $date_to = $dateTo->format("Y-m-d");
        }

        $this->dateFrom = $date_from;
        $this->dateTo = $date_to;
    }

    /**
     * @param $value
     * @return string
     */
    private function valueToYesNo($value)
    {
        if ($value) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

}