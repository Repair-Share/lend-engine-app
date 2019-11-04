<?php

namespace AppBundle\Controller\Admin\Maintenance;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StartMaintenanceController extends Controller
{

    /**
     * @Route("admin/maintenance/{id}/start", requirements={"id": "\d+"}, name="start_maintenance")
     */
    public function startMaintenance($id)
    {
        /** @var \AppBundle\Services\Maintenance\MaintenanceService $service */
        $service = $this->get("service.maintenance");

        $startedAt = new \DateTime();
        $data = [
            'id' => $id,
            'startedAt' => $startedAt
        ];
        if ($maintenance = $service->beginMaintenance($data)) {
            $this->addFlash("success", "Maintenance started.");
        } else {
            foreach ($service->errors AS $error) {
                $this->addFlash("error", $error);
            }
        }

        return $this->redirectToRoute('item', ['id' => $maintenance->getInventoryItem()->getId()]);
    }

}