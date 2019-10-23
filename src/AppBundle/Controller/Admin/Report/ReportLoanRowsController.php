<?php

namespace AppBundle\Controller\Admin\Report;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportLoanRowsController extends Controller
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
        $statusArray[] = array('id' => Loan::STATUS_CANCELLED, 'name' => 'Cancelled');
        $statusArray[] = array('id' => Loan::STATUS_RESERVED, 'name' => 'Reserved');

        $this->loanStatuses = $statusArray;
    }

    /**
     * @Route("admin/report/all_items", name="report_all_items")
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
            'Loan',
            'Status',
            'Item',
            'Code',
            'Serial',
            'Member',
            'Email',
            'Checked out',
            'Checked in',
            'Fee',
            'Deposit',
        );

        /** @var \AppBundle\Services\Report\ReportLoanedItems $report */
        $report = $this->get('report.all_items');
        $this->setDateRange($request);

        // Set up filters
        $filter = array(
            'search'    => $request->get('search'),
            'tagIds'    => $request->get('tag_ids'),
            'statuses'  => $request->get('statuses'),
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {
            /** @var $reportRow \AppBundle\Entity\loanRow */
            
            if ($reportRow->getCheckedOutAt()) {
                $checkedOutAt = $reportRow->getCheckedOutAt()->format("d M Y g:i a");
            } else {
                $checkedOutAt = '';
            }

            if ($reportRow->getCheckedInAt()) {
                $checkedInAt = $reportRow->getCheckedInAt()->format("d M Y g:i a");
            } else {
                $checkedInAt = '';
            }

            if ($reportRow->getDeposit()) {
                $depositAmount = $reportRow->getDeposit()->getAmount();
            } else {
                $depositAmount = '';
            }
            
            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $reportRow->getLoan()->getId(),
                    $reportRow->getLoan()->getStatus(),
                    $reportRow->getInventoryItem()->getName(),
                    $reportRow->getInventoryItem()->getSku(),
                    $reportRow->getInventoryItem()->getSerial(),
                    $reportRow->getLoan()->getContact()->getName(),
                    $reportRow->getLoan()->getContact()->getEmail(),
                    $checkedOutAt,
                    $checkedInAt,
                    $reportRow->getFee(),
                    $depositAmount,
                )
            );
            $n++;
        }

        return $this->render('report/report_loan_rows.html.twig', array(
            'pageTitle' => 'Loan item detail report',
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