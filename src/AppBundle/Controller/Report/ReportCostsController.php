<?php

namespace AppBundle\Controller\Report;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportCostsController extends Controller
{

    protected $dateFrom;

    protected $dateTo;

    public function __construct()
    {

    }

    /**
     * @Route("admin/report/report_costs", name="report_costs")
     */
    public function CostsReport(Request $request)
    {
        $tableRows = array();

        if ($request->get('group_by') == 'item') {
            $tableHeader = array(
                'Item',
                'Code',
                'Serial',
                'Cost'
            );
        } else {
            $tableHeader = array(
                'Date',
                'Item',
                'Code',
                'Serial',
                'Cost',
                'Notes'
            );
        }

        /** @var \AppBundle\Services\Report\ReportCosts $report */
        $report = $this->get('report.costs');
        $this->setDateRange($request);

        // Set up filters
        $filter = array(
            'search' => $request->get('search'),
            'group_by' => $request->get('group_by'),
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {

            if ($request->get('group_by') == 'item') {
                $tableRows[] = array(
                    'id' => $n,
                    'data' => array(
                        $reportRow['item_name'],
                        $reportRow['item_sku'],
                        $reportRow['item_serial'],
                        $reportRow['amount']
                    )
                );
            } else {
                $tableRows[] = array(
                    'id' => $n,
                    'data' => array(
                        $reportRow['date']->format('d M Y'),
                        $reportRow['item_name'],
                        $reportRow['item_sku'],
                        $reportRow['item_serial'],
                        $reportRow['amount'],
                        $reportRow['note']
                    )
                );
            }

            $n++;
        }

        return $this->render('report/report_costs.html.twig', array(
            'pageTitle' => 'Item costs report',
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