<?php

namespace AppBundle\Controller\Admin\Maintenance;

use AppBundle\Entity\Maintenance;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ScheduleMaintenanceController extends Controller
{

    /**
     * @Route("admin/maintenance/item-{itemId}/plan-{planId}", requirements={"itemId": "\d+", "planId": "\d+"}, name="schedule_maintenance")
     */
    public function scheduleMaintenance(Request $request, $itemId, $planId)
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
            $locationId = $request->get('moveToLocation');
            $note = $request->get('note');

            $data = [
                'id' => $maintenanceId,
                'itemId' => $itemId,
                'planId' => $planId,
                'date' => $maintenanceDate,
                'locationId' => $locationId,
                'note' => $note
            ];

            if ($maintenance = $service->scheduleMaintenance($data)) {
                $this->addFlash('success', $messageText);

                if ($request->get('notify')) {
                    $this->sendEmail($maintenance);
                }

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

        // Get valid locations for "move" workflow (excludes on-loan)
        /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
        $locationRepo = $em->getRepository('AppBundle:InventoryLocation');
        $locations = $locationRepo->findOrderedByName('notOnLoan');

        $date = new \DateTime();
        $defaultDate = $date->format("D M d Y");

        return $this->render(
            'maintenance/schedule_maintenance.html.twig',
            [
                'title' => $title,
                'subTitle' => '',
                'item' => $item,
                'plan' => $plan,
                'locations' => $locations,
                'defaultDate' => $defaultDate
            ]
        );
    }

    /**
     * @param Maintenance $maintenance
     * @return bool
     */
    private function sendEmail(Maintenance $maintenance)
    {
        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->container->get('service.contact');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $provider = $maintenance->getAssignedTo();

        $token = $contactService->generateAccessToken($provider);

        $loginUri = $tenantService->getTenant(false)->getDomain(true);
        $loginUri .= '/access?t='.$token.'&e='.urlencode($provider->getEmail());
        $loginUri .= '&r=/admin/maintenance/'.$maintenance->getId();

        $message = $this->renderView(
            'emails/maintenance_due.html.twig',
            [
                'assignee' => $provider,
                'maintenance' => [$maintenance],
                'domain' => $tenantService->getAccountDomain(),
                'loginUri' => $loginUri
            ]
        );

        // Send the email
        $subject = "Maintenance has been assigned to you : ".$maintenance->getInventoryItem()->getName();
        if ($emailService->send($provider->getEmail(), $provider->getName(), $subject, $message, false)) {
            $this->addFlash('success', "We've sent an email to " . $provider->getEmail() . ".");
        } else if ($emailService->getErrors() > 0) {
            foreach ($emailService->getErrors() AS $msg) {
                $this->addFlash('error', $msg);
            }
        }

        return true;

    }

}