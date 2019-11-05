<?php

namespace AppBundle\Controller\Admin\Maintenance;

use AppBundle\Entity\Maintenance;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ScheduleMaintenanceController extends Controller
{

    /**
     * @Route("admin/maintenance/{itemId}/{planId}", requirements={"itemId": "\d+", "planId": "\d+"}, name="schedule_maintenance")
     */
    public function membershipExtend(Request $request, $itemId, $planId)
    {
        /** @var \AppBundle\Services\Maintenance\MaintenanceService $service */
        $service = $this->get("service.maintenance");

        if ($maintenanceId = $request->get('id')) {
            $title = 'Update maintenance date';
            $messageText = 'Maintenance date updated OK';
        } else {
            $title = 'Schedule maintenance';
            $messageText = 'Maintenance scheduled OK';
        }

        if ($request->get('maintenance_date')) {

            $maintenanceDate = new \DateTime($request->get('maintenance_date'));

            $data = [
                'id' => $maintenanceId,
                'itemId' => $itemId,
                'planId' => $planId,
                'date' => $maintenanceDate
            ];

            if ($maintenance = $service->scheduleMaintenance($data)) {
                $this->addFlash('success', $messageText);
            } else {
                foreach ($service->errors AS $error) {
                    $this->addFlash("error", $error);
                }
            }

            if ($maintenanceId) {
                return $this->redirectToRoute('perform_maintenance', ['id' => $maintenanceId]);
            } else {
                return $this->redirectToRoute('item', ['id' => $itemId]);
            }

        }

        $em = $this->getDoctrine()->getManager();

        $itemRepo = $em->getRepository('AppBundle:InventoryItem');
        $item = $itemRepo->find($itemId);

        $planRepo = $em->getRepository('AppBundle:MaintenancePlan');
        $plan = $planRepo->find($planId);

        return $this->render(
            'maintenance/schedule_maintenance.html.twig',
            [
                'title' => $title,
                'subTitle' => '',
                'item' => $item,
                'plan' => $plan
            ]
        );
    }

}