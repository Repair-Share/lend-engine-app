<?php

namespace AppBundle\Controller\Admin\Report;

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
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('AppBundle:ProductTag')->findAllOrderedByName();

        /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
        $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
        $customFields = $fieldRepo->findAllOrderedBySort();

        /** @var \AppBundle\Repository\ProductFieldSelectOptionRepository $fieldSelectOptionRepo */
        $fieldSelectOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ProductFieldSelectOption');

        $tableRows = array();

        $tableHeader = array(
            'Name',
            'Total times loaned',
            'Total fees'
        );

        /** @var \AppBundle\Services\Report\ReportLoanedItems $report */
        $report = $this->get('report.items');
        $this->setDateRange($request);

        // Set up filters
        // @TODO remove direct dependency on REQUEST here
        $filter = array(
            'search'    => $request->get('search'),
            'group_by'  => $request->get('group_by'),
            'tagIds'    => $request->get('tag_ids'),
            'statuses'  => $request->get('statuses'),
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        );

        // Main report data
        $data = $report->run($filter);

        // Get the extra fees for the same dates
        $itemFeesByItemName = [];
        $itemFeesCaptured = false;
        if ($request->get('include_other_fees') == 'yes') {
            if ($request->get('group_by') == 'product') {
                /** @var \AppBundle\Services\Report\ReportPayments $paymentReport */
                $paymentReport = $this->get('report.payments');
                $payments = $paymentReport->run($filter);
                foreach ($payments AS $payment) {
                    if (!isset($itemFeesByItemName[$payment['itemName']])) {
                        $itemFeesByItemName[$payment['itemName']] = 0;
                    }
                    if ($payment['type'] == 'FEE' && $payment['note'] != null) {
                        $itemFeesByItemName[$payment['itemName']] += $payment['amount'];
                    }
                }
                $itemFeesCaptured = true;
            } else {
                $this->addFlash("info", "Extra fees are only included when you report by item name.");
            }
        }

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

            $itemName = $reportRow['group_name'];

            $fees = $reportRow['fees'];
            if ($itemFeesCaptured == true && isset($itemFeesByItemName[$itemName])) {
                $fees += $itemFeesByItemName[$itemName];
            }

            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $reportRow['group_name'],
                    $reportRow['qty'],
                    number_format($fees, 2)
                )
            );
            $n++;
        }

        return $this->render('report/report_items.html.twig', array(
            'pageTitle' => 'Loans by item',
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