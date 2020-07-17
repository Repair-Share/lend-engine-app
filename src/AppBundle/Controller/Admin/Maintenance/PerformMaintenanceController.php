<?php

namespace AppBundle\Controller\Admin\Maintenance;

use AppBundle\Entity\Maintenance;
use AppBundle\Form\Type\MaintenanceType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformMaintenanceController extends Controller
{

    /**
     * @Route("admin/maintenance/{id}", requirements={"id": "\d+"}, name="perform_maintenance")
     */
    public function performMaintenance(Request $request, $id)
    {
        /** @var \AppBundle\Services\Maintenance\MaintenanceService $service */
        $service = $this->get("service.maintenance");

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Entity\Maintenance $maintenance */
        $maintenance = $service->get($id);

        $originalAssignee = $maintenance->getAssignedTo();

        $options = [
            'em'         => $em,
            'action'     => $this->generateUrl('perform_maintenance', ['id' => $id])
        ];
        $form = $this->createForm(MaintenanceType::class, $maintenance, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($request->get('submitForm') == 'start') {
                $maintenance->setStatus(Maintenance::STATUS_IN_PROGRESS);
            } else if ($request->get('submitForm') == 'complete') {
                $maintenance->setStatus(Maintenance::STATUS_COMPLETED);
                $maintenance->setCompletedBy($this->getUser());
            }

            if ($form->get('status')->getData() == "completed") {

                if (!$maintenance->getCompletedBy()) {
                    $maintenance->setCompletedBy($this->getUser());
                }

                if ($form->get('createNext')->getData() == true && $maintenance->getMaintenancePlan()->getInterval() > 0) {

                    $next = new Maintenance();
                    $dueAt = clone($maintenance->getDueAt());
                    $n = $maintenance->getMaintenancePlan()->getInterval();
                    $dueAt->modify("+{$n} months");
                    $next->setDueAt($dueAt);
                    $next->setInventoryItem($maintenance->getInventoryItem());
                    $next->setMaintenancePlan($maintenance->getMaintenancePlan());
                    $service->save($next);

                    $this->addFlash("success", "The next maintenance has been scheduled for {$n} months time.");
                }

            }

            $service->save($maintenance);

            // Send an email to the new assignee
            if ($maintenance->getAssignedTo() != $originalAssignee) {
                $service->notifyAssignee($maintenance);
            }

            return $this->redirectToRoute('perform_maintenance', ['id' => $id]);
        }

        return $this->render(
            'maintenance/perform_maintenance.html.twig',
            [
                'form' => $form->createView(),
                'maintenance' => $maintenance
            ]
        );
    }



}