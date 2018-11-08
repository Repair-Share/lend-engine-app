<?php

namespace AppBundle\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportChildrenController extends Controller
{

    protected $dateFrom;

    protected $dateTo;

    public function __construct()
    {

    }

    /**
     * @Route("admin/report/report_children", name="report_children")
     */
    public function ChildrenReport(Request $request)
    {
        $tableRows = array();

        $tableHeader = array(
            '',
            'Count'
        );

        /** @var \AppBundle\Services\Report\ReportChildren $report */
        $report = $this->get('report.children');
        $this->setDateRange($request);

        // Set up filters
        $filter = array(
            'has_membership' => $request->get('has_membership'),
            'group_by' => $request->get('group_by')
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {

            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $reportRow['grouping_name'],
                    $reportRow['qty']
                )
            );

            $n++;
        }

        return $this->render('report/report_children.html.twig', array(
            'pageTitle' => 'Children',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo
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

}