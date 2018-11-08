<?php

namespace AppBundle\Controller\Report;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportLoanedItemsController extends Controller
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
     * @Route("admin/report/report_items", name="report_items")
     */
    public function ItemsReport(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('AppBundle:ProductTag')->findAllOrderedByName();

        /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
        $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
        $customFields = $fieldRepo->findAllOrderedBySort();

        /** @var \AppBundle\Repository\ProductFieldSelectOptionRepository $fieldSelectOptionRepo */
        $fieldSelectOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ProductFieldSelectOption');

        $tableRows = array();

        $tableHeader = array(
            '',
            'Total times loaned',
            'Total fees'
        );

        /** @var \AppBundle\Services\Report\ReportLoanedItems $report */
        $report = $this->get('report.items');
        $this->setDateRange($request);

        // Set up filters
        $filter = array(
            'search'    => $request->get('search'),
            'group_by'  => $request->get('group_by'),
            'tagIds'    => $request->get('tag_ids'),
            'statuses'  => $request->get('statuses'),
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
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
                    $reportRow['qty'],
                    $reportRow['fees']
                )
            );
            $n++;
        }

        return $this->render('report/report_items.html.twig', array(
            'pageTitle' => 'Loaned items report',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows,
            'tags' => $tags,
            'productFields' => $customFields,
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