<?php

namespace AppBundle\Controller\Report;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportNonLoanedItemsController extends Controller
{

    protected $dateFrom;

    protected $dateTo;

    public function __construct()
    {

    }

    /**
     * @Route("admin/report/non_loaned_items", name="non_loaned_items")
     */
    public function NonLoanedItemsReport(Request $request)
    {

        $tableRows = array();

        $tableHeader = array(
            'Code',
            'Serial #',
            'Name'
        );

        /** @var \AppBundle\Services\Report\ReportNonLoanedItems $report */
        $report = $this->get('report.non-loaned-items');

        // Set up filters
        $filter = array(
            'search'    => $request->get('search'),
            'time'      => $request->get('time')
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {
            $name = $reportRow['name'];
            $itemPath = $this->generateUrl('item', ['id' => $reportRow['id']]);
            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $reportRow['sku'],
                    $reportRow['serial'],
                    '<a href="'.$itemPath.'">'.$name.'</a>'
                )
            );
            $n++;
        }

        return $this->render('report/report_non_loaned_items.html.twig', array(
            'pageTitle' => 'Non-loaned items report',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows
        ));
    }

}