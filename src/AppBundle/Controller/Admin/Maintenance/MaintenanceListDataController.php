<?php

namespace AppBundle\Controller\Admin\Maintenance;

use AppBundle\Entity\Maintenance;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceListDataController extends Controller
{

    /**
     * JSON responder for DataTables AJAX product list
     * @Route("admin/dt/maintenance/list", name="dt_maintenance_list")
     */
    public function maintenanceDataList(Request $request)
    {
        /** @var \AppBundle\Services\Maintenance\MaintenanceService $service */
        $service = $this->get('service.maintenance');

        $data = [];
        $draw = $request->get('draw');

        $search = $request->get('search');

        $searchString = '';
        if (isset($search['value'])) {
            $searchString = $search['value'];
        }

        $start  = $request->get('start');
        $length = $request->get('length');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        if ($maintenancePlan = $request->get('maintenancePlanId')) {
            $filter['maintenancePlanId'] = $maintenancePlan;
        }
        if ($statuses = $request->get('statuses')) {
            $filter['statuses'] = $statuses;
        }
        if ($status = $request->get('assignedTo')) {
            $filter['assignedTo'] = $status;
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
                    $sort['column'] = 'itemName';
                    break;
                case 1:
                    $sort['column'] = 'itemCode';
                    break;
                case 2:
                    $sort['column'] = 'itemSerial';
                    break;
                case 4:
                    $sort['column'] = 'due';
                    break;
                case 6:
                    $sort['column'] = 'status';
                    break;
                case 8:
                    $sort['column'] = 'cost';
                    break;
            }
        }

        /***** MAIN QUERY ****/

        $searchResults = $service->search($start, $length, $filter, $sort);
        $totalRecords = $searchResults['totalResults'];
        $maintenanceResults     = $searchResults['data'];

        /** @var \AppBundle\Entity\Maintenance $maintenance */
        foreach ($maintenanceResults AS $maintenance) {

            $row = [];

            $editUrl   = $this->generateUrl('perform_maintenance', array('id' => $maintenance->getId()));
            $row[] = $maintenance->getInventoryItem()->getName();

            $row[] = $maintenance->getInventoryItem()->getSku();
            $row[] = $maintenance->getInventoryItem()->getSerial();

            $row[] = $maintenance->getMaintenancePlan()->getName();

            $row[] = '<a href="'.$editUrl.'">'.$maintenance->getDueAt()->format("D j F Y").'</a>';

            if ($maintenance->getAssignedTo()) {
                $row[] = $maintenance->getAssignedTo()->getName();
            } else {
                $row[] = '';
            }

            $status = '';
            switch ($maintenance->getStatus()) {
                case Maintenance::STATUS_PLANNED:
                    $status = '<label class="label label-default">Planned</label>';
                    break;
                case Maintenance::STATUS_IN_PROGRESS:
                    $status = '<label class="label bg-aqua">In progress</label>';
                    break;
                case Maintenance::STATUS_OVERDUE:
                    $status = '<label class="label bg-red">Overdue</label>';
                    break;
                case Maintenance::STATUS_COMPLETED:
                    $status = '<label class="label bg-green">Completed</label>';
                    break;
                case Maintenance::STATUS_SKIPPED:
                    $status = '<label class="label bg-orange">Skipped</label>';
                    break;
            }
            $row[] = $status;

            if ($maintenance->getFileAttachments()->count() > 0) {
                $row[] = '<i class="fa fa-paperclip" style="color: #000"></i>';
            } else {
                $row[] = '';
            }

            $row[] = number_format($maintenance->getTotalCost(), 2);

            $row[] = '<a href="'.$editUrl.'" class="btn btn-xs btn-default">Open</a>';

            $data[] = $row;

        }

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $totalRecords,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

}