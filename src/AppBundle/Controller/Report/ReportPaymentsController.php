<?php

namespace AppBundle\Controller\Report;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Payment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportPaymentsController extends Controller
{

    protected $dateFrom;

    protected $dateTo;

    /**
     * @Route("admin/report/report_payments", name="report_payments")
     */
    public function PaymentsReport(Request $request)
    {
        $tableRows = array();

        $tableHeader = array(
            'Date',
            'Member',
            'Type',
            'Payment method',
            'Note',
            'Amount',
        );

        /** @var \AppBundle\Services\Report\ReportPayments $report */
        $report = $this->get('report.payments');
        $this->setDateRange($request);

        // Set up filters
        $filter = array(
            'search' => $request->get('search'),
            'payment_method' => $request->get('payment_method'),
            'payment_type' => $request->get('payment_type'),
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        );

        $data = $report->run($filter);

        $n = 0;
        foreach ($data AS $reportRow) {

            $amount = number_format($reportRow['amount'], 2);
            if (in_array($reportRow['type'], [Payment::PAYMENT_TYPE_PAYMENT, Payment::PAYMENT_TYPE_DEPOSIT])) {
                $fee = '<div style="color:#419f43; text-align: right">'.$amount.'</div>';
            } else {
                $fee = '<div style="color:#b90009; text-align: right">-'.$amount.'</div>';
            }

            if ($reportRow['deposit_id']) {
                $type = 'Deposit';
            } else if ($reportRow['loan_id']) {
                $type = 'Loan';
            } else if ($reportRow['membership_id']) {
                $type = 'Membership';
            } else {
                $type = '-';
            }

            $contactUrl = $this->generateUrl('contact', ['id' => $reportRow['contact_id']]);

            $date = $reportRow['date'];

            $paymentMethodString = $reportRow['payment_method_name'];
            if ($reportRow['pspCode']) {
                $paymentMethodString .= '<div class="help-block">'.$reportRow['pspCode'].'</div>';
            }

            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $date->format('d M Y H:i'),
                    '<a href="'.$contactUrl.'">'.$reportRow['contact_name'].'</a>',
                    $type,
                    $paymentMethodString,
                    $reportRow['note'],
                    $fee,
                )
            );
            $n++;
        }

        /** @var \AppBundle\Repository\PaymentMethodRepository $repo */
        $repo = $this->getDoctrine()->getRepository('AppBundle:PaymentMethod');

        // Include inactive ones for the filter
        $paymentMethods = $repo->findAllOrderedByName(true);

        return $this->render('report/report_payments.html.twig', array(
            'pageTitle' => 'Payments report',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
            'paymentMethods' => $paymentMethods
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