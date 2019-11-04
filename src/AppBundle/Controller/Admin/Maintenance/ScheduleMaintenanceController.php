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

        if ($request->get('maintenance_date')) {

            $maintenanceDate = new \DateTime($request->get('maintenance_date'));

            /** @var \AppBundle\Services\Maintenance\MaintenanceService $service */
            $service = $this->get("service.maintenance");

            $data = [
                'itemId' => $itemId,
                'planId' => $planId,
                'date' => $maintenanceDate
            ];

            if ($maintenance = $service->scheduleMaintenance($data)) {
                $this->addFlash('success','Maintenance scheduled OK.');
            } else {
                foreach ($service->errors AS $error) {
                    $this->addFlash("error", $error);
                }
            }

            return $this->redirectToRoute('item', ['id' => $itemId]);

        }

        $em = $this->getDoctrine()->getManager();

        $itemRepo = $em->getRepository('AppBundle:InventoryItem');
        $item = $itemRepo->find($itemId);

        $planRepo = $em->getRepository('AppBundle:MaintenancePlan');
        $plan = $planRepo->find($planId);

        return $this->render(
            'maintenance/schedule_maintenance.html.twig',
            [
                'title' => 'Schedule maintenance',
                'subTitle' => '',
                'item' => $item,
                'plan' => $plan
            ]
        );
    }

}