<?php

namespace AppBundle\Controller\Admin\Maintenance;

use AppBundle\Entity\Maintenance;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class MaintenanceListController extends Controller
{

    private $filterDateFrom;
    private $filterDateTo;

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/maintenance/list", name="maintenance_list")
     */
    public function contactList(Request $request)
    {
        $pageTitle = 'Maintenance schedule';

        $searchString = $request->get('search');

        $this->setDateRange($request);

        /** @var \AppBundle\Repository\MaintenancePlanRepository $maintenancePlanRepo */
        $maintenancePlanRepo = $this->getDoctrine()->getRepository('AppBundle:MaintenancePlan');
        $maintenancePlans = $maintenancePlanRepo->findAllOrderedByName(true);

        /** @var \AppBundle\Repository\ContactRepository $repo */
        $repo = $this->getDoctrine()->getRepository('AppBundle:Contact');
        $team = $repo->findAllStaff();

        $statuses = [
            Maintenance::STATUS_OVERDUE => 'Overdue',
            Maintenance::STATUS_PLANNED => 'Planned',
            Maintenance::STATUS_IN_PROGRESS => 'In progress',
            Maintenance::STATUS_COMPLETED => 'Completed',
            Maintenance::STATUS_SKIPPED => 'Skipped',
        ];

        if (!$selectedStatuses = $request->get('statuses')) {
            $selectedStatuses = ['DRAFT', 'PUBLISHED'];
        }

        return $this->render(
            'maintenance/maintenance_list.html.twig',
            [
                'pageTitle'    => $pageTitle,
                'searchString' => $searchString,
                'date_from'    => $this->filterDateFrom,
                'date_to'      => $this->filterDateTo,
                'team'         => $team,
                'maintenancePlans' => $maintenancePlans,
                'maintenanceStatuses' => $statuses,
                'selectedStatuses' => $selectedStatuses
            ]
        );
    }

    /**
     * Default date ranges for contact list
     * @param Request $request
     */
    private function setDateRange(Request $request)
    {

        if ($request->get('date_from')) {
            $date_from = $request->get('date_from');
        } else {
            $dateFrom = new \DateTime();
            $dateFrom->modify("-5 years");
            $date_from = $dateFrom->format("Y-m-d");
        }

        if ($request->get('date_to')) {
            $date_to = $request->get('date_to');
        } else {
            $dateTo = new \DateTime();
            $dateTo->modify("now");
            $date_to = $dateTo->format("Y-m-d");
        }

        // Set the filters to the same value as the data
        $this->filterDateFrom = $date_from;
        $this->filterDateTo   = $date_to;

    }

}