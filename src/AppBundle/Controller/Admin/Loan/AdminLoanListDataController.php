<?php

namespace AppBundle\Controller\Admin\Loan;

use AppBundle\Helpers\DateTimeHelper;
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

        $searchString = '';
        if (isset($search['value'])) {
            $searchString = $search['value'];
        }

        $start  = $request->get('start');
        $length = $request->get('length');

        // A hack since the search query seems to be returning number of results based on item rows rather than loans
        $length += 50;

        $columns = $request->get('columns');
        if (isset($columns[1]['search']['value']) && $columns[1]['search']['value']) {
            $statusFilter = $columns[1]['search']['value'];
        } else {
            // For first page load from menu
            $statusFilter = $request->get('status');
        }

        /** @var $repo \AppBundle\Repository\LoanRowRepository */
        $repo = $em->getRepository('AppBundle:LoanRow');

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
        if ($request->get('current_site')) {
            $filter['current_site'] = $request->get('current_site');
        }
        if ($request->get('from_site')) {
            $filter['from_site'] = $request->get('from_site');
        }
        if ($request->get('to_site')) {
            $filter['to_site'] = $request->get('to_site');
        }

        if ($sortData = $request->get('order')) {
            $sortByColumnId = $sortData[0]['column']; // assumes single column sort
            $sort['direction'] = $sortData[0]['dir'];
            switch ($sortByColumnId) {
                case 0:
                    $sort['column'] = 'id';
                    break;
                case 4:
                    $sort['column'] = 'timeOut';
                    break;
                case 5:
                    $sort['column'] = 'timeIn';
                    break;
            }
        } else { // Default sorting options by the status

            switch ($statusFilter) {
                case 'ACTIVE': // On loan
                    $sort = [
                        'column'    => 'timeIn',
                        'direction' => 'ASC'
                    ];
                    break;
                case 'RESERVED': // Reservations
                case 'PENDING': // Pending
                case 'OVERDUE': // Overdue
                    $sort = [
                        'column'    => 'timeOut',
                        'direction' => 'ASC'
                    ];
                    break;
                default:
                    $sort = [
                        'column'    => 'id',
                        'direction' => 'DESC'
                    ];
            }

        }

        $filter['excludeStockItems'] = true;

        // Modify times to match local time for UI
        // Not sure why DateTime format() here is not working
        $tz       = $settingsService->getSettingValue('org_timezone');
        $localNow = DateTimeHelper::getLocalTime($tz, new \DateTime());

        $loanData = $repo->search($start, $length, $filter, $sort, $tz);

        /** @var \AppBundle\Entity\LoanRow $loanRow */
        foreach ($loanData['data'] AS $loanRow) {

            /** @var $loan \AppBundle\Entity\Loan */
            $loan = $loanRow->getLoan();

            $row = [];

            $editUrl   = $this->generateUrl('public_loan', array('loanId' => $loan->getId()));

            if (($loan->getStatus() == Loan::STATUS_CLOSED && $localNow > $loanRow->getDueInAt()) || $loanRow->getCheckedInAt()) {
                $status = '<span class="label bg-dim">'.Loan::STATUS_CLOSED.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_PENDING) {
                $status = '<span class="label bg-gray">'.Loan::STATUS_PENDING.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_ACTIVE && $localNow < $loanRow->getDueInAt()) {
                $status = '<span class="label bg-teal">ON LOAN</span>';
            } else if ($loan->getStatus() == Loan::STATUS_RESERVED) {
                $status = '<span class="label bg-orange">'.Loan::STATUS_RESERVED.'</span>';
            } else if ($loan->getStatus() == Loan::STATUS_CANCELLED) {
                $status = '<span class="label bg-dim">'.Loan::STATUS_CANCELLED.'</span>';
            } else {
                $status = '<span class="label bg-red">'.Loan::STATUS_OVERDUE.'</span>';
            }

            if ($loan->getCollectFrom() == "post") {
                $status .= '<div style="padding:4px; font-size: 11px">DELIVER</div>';
            }

            // Modify UTC database times to match local time
            $i = DateTimeHelper::getLocalTime($tz, $loanRow->getDueInAt());
            $loanRow->setDueInAt($i);
            $o = DateTimeHelper::getLocalTime($tz, $loanRow->getDueOutAt());
            $loanRow->setDueOutAt($o);

            $loanInfo = '<a href="'.$editUrl.'">'.$loanRow->getInventoryItem()->getName().'</a>';
            $loanInfo .= '<div class="sub-text">'.$loan->getContact()->getFirstName().' '.$loan->getContact()->getLastName().' : '.$loan->getContact()->getEmail().'</div>';

            if ($loan->getStatus() == Loan::STATUS_RESERVED && $loanRow->getInventoryItem()->getInventoryLocation()) {
                if ($loanRow->getInventoryItem()->getInventoryLocation()->getSite() != $loanRow->getSiteFrom()) {
                    $loanInfo .= '<span style="color: #de7c34">Item needs moving from ' .$loanRow->getInventoryItem()->getInventoryLocation()->getSite()->getName().'</span>';
                }
            }

            $row[] = '<a title data-original-title="Open" href="'.$editUrl.'">'.$loan->getId().'</a>';
            $row[] = $status;
            $row[] = $loanInfo;
            $row[] = $loanRow->getInventoryItem()->getSku();

            $fromSite = '';
            if ($settingsService->getSettingValue('multi_site') && $loanRow->getSiteFrom()) {
                $fromSite = '<div class="sub-text">'.$loanRow->getSiteFrom()->getName().'</div>';
            }
            $row[] = $loanRow->getDueOutAt()->format("D. d M Y").'<div style="font-size: 12px">'.$loanRow->getDueOutAt()->format("g:i a").'</div>'.$fromSite;

            $toSite = '';
            if ($settingsService->getSettingValue('multi_site') && $loanRow->getSiteTo()) {
                $toSite = '<div class="sub-text">'.$loanRow->getSiteTo()->getName().'</div>';
            }
            $row[] = $loanRow->getDueInAt()->format("D. d M Y").'<div style="font-size: 12px">'.$loanRow->getDueInAt()->format("g:i a").'</div><div class="sub-text">'.$toSite;

            $row[] = number_format($loanRow->getFee(), 2);

            $links = '<li><a href="'.$editUrl.'">Open</a></li>';

            if (!in_array($loan->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                $deleteUrl = $this->generateUrl('loan_delete', array('id' => $loan->getId()));
                $links .= '<li role="separator" class="divider"></li>';
                $links .= '<li><a href="'.$deleteUrl.'" class="delete-link">Delete</a></li>';
            }

            $linkHtml = '
<div class="dropdown hidden-print">
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