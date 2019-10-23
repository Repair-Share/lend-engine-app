<?php

namespace AppBundle\Controller\Admin\Loan;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Loan;

class AdminLoanListController extends Controller
{

    private $filterDateFrom;
    private $filterDateTo;
    private $dateOutFrom;
    private $dateOutTo;
    private $dateInFrom;
    private $dateInTo;

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/loan/list", name="loan_list")
     */
    public function listAction(Request $request)
    {
        $statuses = array();
        $statusKeys = array(
            Loan::STATUS_PENDING,
            Loan::STATUS_ACTIVE,
            Loan::STATUS_OVERDUE,
            Loan::STATUS_RESERVED,
            Loan::STATUS_CLOSED,
            Loan::STATUS_CANCELLED
        );
        foreach ($statusKeys AS $key) {
            if ($request->get('status') == $key) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $statuses[] = array(
                'id'   => $key,
                'name' => ucfirst(strtolower($key)),
                'selected' => $selected
            );
        }

        $searchString = $request->get('search');

        $this->setDateRange($request);

        /** @var \AppBundle\Services\Loan\LoanService $loanService */
        $loanService = $this->get('service.loan');

        $pending  = (int)$loanService->countLoans(Loan::STATUS_PENDING);
        $active   = (int)$loanService->countLoans(Loan::STATUS_ACTIVE);
        $overdue  = (int)$loanService->countLoans(Loan::STATUS_OVERDUE);
        $reserved = (int)$loanService->countLoans(Loan::STATUS_RESERVED);

        return $this->render(
            'loan/loan_list.html.twig',
            array(
                'searchString'  => $searchString,
                'statuses'      => $statuses,
                'date_from'     => $this->filterDateFrom,
                'date_to'       => $this->filterDateTo,
                'countPending'  => $pending,
                'countActive'   => $active,
                'countOverdue'  => $overdue,
                'countReserved' => $reserved
            )
        );
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
            $dateFrom->modify("-12 months");
            $date_from = $dateFrom->format("Y-m-d");
        }

        if ($request->get('date_to')) {
            $date_to = $request->get('date_to');
        } else {
            $dateTo = new \DateTime();
            $dateTo->modify("+1 month");
            $date_to = $dateTo->format("Y-m-d");
        }

        if ($request->get('date_type') == 'date_out') {
            $this->dateOutFrom = $date_from;
            $this->dateOutTo   = $date_to;
        } else {
            $this->dateInFrom = $date_from;
            $this->dateInTo   = $date_to;
        }

        // Set the filters to the same value as the data
        $this->filterDateFrom = $date_from;
        $this->filterDateTo   = $date_to;

    }

}