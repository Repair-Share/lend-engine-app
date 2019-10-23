<?php

namespace AppBundle\Controller\Admin\Loan;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Loan;

class AdminLoanListDataController extends Controller
{

    /**
     * JSON responder for DataTables AJAX loan list
     * @Route("admin/dt/loan/list", name="dt_loan_list")
     */
    public function loanListData(Request $request)
    {
        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $em = $this->getDoctrine()->getManager();
        $data = array();

        $draw = $request->get('draw');

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        // A hack since the search query seems to be returning number of results based on item rows rather than loans
        $length += 50;

        $columns = $request->get('columns');
        if ($columns[1]['search']['value']) {
            $statusFilter = $columns[1]['search']['value'];
        } else {
            // For first page load from menu
            $statusFilter = $request->get('status');
        }

        /** @var $repo \AppBundle\Repository\LoanRepository */
        $repo = $em->getRepository('AppBundle:Loan');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        if ($statusFilter) {
            $filter['status'] = $statusFilter;
        }
        if ($request->get('date_from')) {
            $filter['date_from'] = $request->get('date_from');
        }
        if ($request->get('date_to')) {
            $filter['date_to'] = $request->get('date_to');
        }
        if ($request->get('date_type')) {
            $filter['date_type'] = $request->get('date_type');
        }

        $sort = [
            'column'    => 'id',
            'direction' => 'DESC'
        ];
        if ($sortData = $request->get('order')) {
            $sortByColumnId = $sortData[0]['column']; // assumes single column sort
            $sort['direction'] = $sortData[0]['dir'];
            switch ($sortByColumnId) {
                case 0:
                    $sort['column'] = 'id';
                    break;
                case 3:
                    $sort['column'] = 'timeOut';
                    break;
                case 4:
                    $sort['column'] = 'timeIn';
                    break;
                case 5:
                    $sort['column'] = 'totalFee';
                    break;
            }
        }

        $loanData = $repo->findLoans($start, $length, $filter, $sort);

        // Modify times to match local time for UI
        // Not sure why DateTime format() here is not working
        $tz = $settingsService->getSettingValue('org_timezone');
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = $timeZone->getOffset($utc)/3600;

        foreach ($loanData['data'] AS $loan) {

            $row = [];

            /** @var $loan \AppBundle\Entity\Loan */
            $editUrl   = $this->generateUrl('public_loan', array('loanId' => $loan->getId()));

            if ($loan->getStatus() == Loan::STATUS_CLOSED) {
                $status = '<span class="label bg-dim">'.Loan::STATUS_CLOSED.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_PENDING) {
                $status = '<span class="label bg-gray">'.Loan::STATUS_PENDING.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_ACTIVE) {
                $status = '<span class="label bg-teal">ON LOAN</span>';
            } else if ($loan->getStatus() == Loan::STATUS_RESERVED) {
                $status = '<span class="label bg-orange">'.Loan::STATUS_RESERVED.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_CANCELLED) {
                $status = '<span class="label bg-dim">'.Loan::STATUS_CANCELLED.'</span>';
            } else {
                $status = '<span class="label bg-red">'.Loan::STATUS_OVERDUE.'</span>';
            }

            // Modify UTC database times to match local time
            foreach ($loan->getLoanRows() AS $loanRow) {
                /** @var $loanRow \AppBundle\Entity\LoanRow */
                $i = $loanRow->getDueInAt()->modify("{$offSet} hours");
                $loanRow->setDueInAt($i);
                $o = $loanRow->getDueOutAt()->modify("{$offSet} hours");
                $loanRow->setDueOutAt($o);
            }

            // Timezone modify
            $ti = $loan->getTimeIn()->modify("{$offSet} hours");
            $loan->setTimeIn($ti);
            $to = $loan->getTimeOut()->modify("{$offSet} hours");
            $loan->setTimeOut($to);

            $loanFromTime = $loan->getTimeOut();
            $loanInfo = $loan->getContact()->getFirstName().' '.$loan->getContact()->getLastName();

            foreach ($loan->getLoanRows() AS $loanRow) {
                /** @var $loanRow \AppBundle\Entity\LoanRow */
                $loanInfo .= '<div class="loan-row" style="font-size:11px; color: #aaa;">'.$loanRow->getInventoryItem()->getName().'</div>';
                if ($loanRow->getDueOutAt()) {
                    // reservations
                    $loanFromTime = $loanRow->getDueOutAt();
                }
            }

            $row[] = '<a title data-original-title="Open" href="'.$editUrl.'">'.$loan->getId().'</a>';
            $row[] = $status;
            $row[] = $loanInfo;
            $row[] = $loanFromTime->format("l d M Y").'<div style="font-size: 12px">'.$loanFromTime->format("g:i a").'</div>';
            $row[] = $loan->getTimeIn()->format("l d M Y").'<div style="font-size: 12px">'.$loan->getTimeIn()->format("g:i a").'</div>';
            $row[] = number_format($loan->getItemsTotal(), 2);

            $links = '<li><a href="'.$editUrl.'">Open</a></li>';

            if (!in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                $deleteUrl = $this->generateUrl('loan_delete', array('id' => $loan->getId()));
                $links .= '<li role="separator" class="divider"></li>';
                $links .= '<li><a href="'.$deleteUrl.'" class="delete-link">Delete</a></li>';
            }

            $linkHtml = '
<div class="dropdown">
  <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
  Action
  <span class="caret"></span>
  </button>
  <ul class="dropdown-menu pull-right">
    '.$links.'
  </ul>
</div>
';

            // Action links
            $row[] = $linkHtml;

            $data[] = $row;

        }

        $count = $loanData['totalResults'];

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $count,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

}