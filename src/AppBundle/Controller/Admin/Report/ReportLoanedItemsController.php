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

        /** @var \AppBundle\Services\Maintenance\MaintenanceService $maintenanceService */
        $maintenanceService = $this->get('service.maintenance');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        if (!$groupBy = $request->get('group_by')) {
            $groupBy = 'item_name';
        }

        $tableRows = array();

        if ($groupBy == 'item_name') {
            $tableHeader = array(
                'Name',
                'Times loaned',
                'Loan fees',
                'Other fees',
                'Price paid',
                'Value/RRP',
                'Maintenance'
            );
            $tableId = 'report-items-by-name';
        } else if ($groupBy == 'item') {
            $tableHeader = array(
                'ID',
                'Name',
                'Code',
                'Serial',
                'Times loaned',
                'Loan fees',
                'Other fees',
                'Price paid',
                'Value/RRP',
                'Maintenance'
            );
            $tableId = 'report-items-by-id';
        } else {
            $tableHeader = array(
                'Name',
                'Times loaned',
                'Loan fees'
            );
            $tableId = 'report-items-by-custom-field';
        }

        /** @var \AppBundle\Services\Report\ReportLoanedItems $report */
        $report = $this->get('report.items');
        $this->setDateRange($request);

        // Set up filters
        $filter = [
            'search'    => $request->get('search'),
            'group_by'  => $request->get('group_by'),
            'tagIds'    => $request->get('tag_ids'),
            'statuses'  => $request->get('statuses'),
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ];

        // Main report data
        $data = $report->run($filter);

        // Get the extra fees for the same dates
        $itemFeesByItemName = [];

        /** @var \AppBundle\Services\Report\ReportPayments $paymentReport */
        $paymentReport = $this->get('report.payments');

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

            $loanFees = $reportRow['fees'];
            $otherFees = 0;

            if ($groupBy == 'item_name') {

                $itemName = $reportRow['group_name'];
                $filter['item_name'] = $itemName;

                // Extension and other fees
                if (isset($itemFeesByItemName[$itemName])) {
                    $otherFees = $itemFeesByItemName[$itemName];
                }

                // The costs incurred in repairing the item
                $costData = $maintenanceService->getTotalCosts($filter);
                $maintenanceCost = $costData['maintenanceCost'];

                $costData = $itemService->getCosts($filter);
                $itemCost = $costData['cost'];
                $itemValue = $costData['value'];

                // Get the other fees (income) - extensions, late fees
                $payments = $paymentReport->run($filter);
                foreach ($payments AS $payment) {
                    if ($payment['type'] == 'FEE' && $payment['note'] != null) {
                        $otherFees += $payment['amount'];
                    }
                }

                $tableRows[] = array(
                    'id' => $n,
                    'data' => array(
                        $reportRow['group_name'],
                        $reportRow['qty'],
                        number_format($loanFees, 2),
                        number_format($otherFees, 2),
                        number_format($itemCost, 2),
                        number_format($itemValue, 2),
                        number_format($maintenanceCost, 2)
                    )
                );

            } else if ($groupBy == 'item') {

                $itemId   = $reportRow['item_id'];

                // Get the other fees (income) - extensions, late fees
                $filter['item_id'] = $itemId;
                $payments = $paymentReport->run($filter);
                foreach ($payments AS $payment) {
                    if ($payment['type'] == 'FEE' && $payment['note'] != null) {
                        $otherFees += $payment['amount'];
                    }
                }

                // The costs incurred in repairing the item
                $costData = $maintenanceService->getTotalCosts($filter);
                $maintenanceCost = $costData['maintenanceCost'];

                $costData = $itemService->getCosts($filter);
                $itemCost = $costData['cost'];
                $itemValue = $costData['value'];

                $itemUrl = $this->generateUrl('item', ['id' => $itemId]);

                $tableRows[] = array(
                    'id' => $n,
                    'data' => array(
                        $reportRow['item_id'],
                        '<a href="'.$itemUrl.'">'.$reportRow['group_name'].'</a>',
                        $reportRow['code'],
                        $reportRow['serial'],
                        $reportRow['qty'],
                        number_format($loanFees, 2),
                        number_format($otherFees, 2),
                        number_format($itemCost, 2),
                        number_format($itemValue, 2),
                        number_format($maintenanceCost, 2)
                    )
                );


            } else {

                $tableRows[] = array(
                    'id' => $n,
                    'data' => array(
                        $reportRow['group_name'],
                        $reportRow['qty'],
                        number_format($loanFees, 2)
                    )
                );

            }

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
            'tableId' => $tableId
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