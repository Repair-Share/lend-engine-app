<?php


namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Payment;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Type\ItemMoveType;
use AppBundle\Form\Type\ItemRemoveType;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

class ItemMoveController extends Controller
{
    /**
     * Modal content for moving items
     * @Route("admin/item/move/{idSet}", name="item_move", defaults={"idSet" = 0})
     */
    public function moveAction(Request $request, $idSet)
    {
        $idSet = trim($idSet, ',');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\WaitingList\WaitingListService $waitingListService */
        $waitingListService = $this->get('service.waiting_list');

        /** @var \AppBundle\Services\Maintenance\MaintenanceService $maintenanceService */
        $maintenanceService = $this->get('service.maintenance');

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Entity\InventoryItem $inventoryItem */
        $inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');

        $idArray = explode(',', $idSet);
        $count = count($idArray);
        if ($count == 1) {
            $inventoryItem = $inventoryItemRepo->find($idArray[0]);
            $existingLocation = $inventoryItem->getInventoryLocation();
            $modalTitle = $inventoryItem->getName();
        } else {
            $existingLocation = null;
            $modalTitle = "Move {$count} items";
        }

        $options = [
            'em'         => $em,
            'location'   => $existingLocation,
            'action'     => $this->generateUrl('item_move', ['idSet' => $idSet])
        ];
        $form = $this->createForm(ItemMoveType::class, null, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $toLocation = $form->get('location')->getData();
            $userNote   = $form->get('notes')->getData();
            $maintenancePlan = $form->get('maintenancePlan')->getData();

            $updatedItems = 0;

            $lastItemId = null;
            $maintenanceActions = [];

            foreach ($idArray AS $itemId) {
                if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
                    $this->addFlash("error", "Item ID {$itemId} cannot be moved - it does not exist.");
                    continue;
                } else {
                    if ($inventoryItem->getInventoryLocation()->getId() == 1) {
                        $this->addFlash("error", "Item ID {$itemId} cannot be moved - it is on loan.");
                        continue;
                    }
                    if ( $inventoryService->itemMove($inventoryItem, $toLocation, null, null, $userNote) ) {
                        $updatedItems++;

                        if ($toLocation->getIsAvailable() == true) {
                            $waitingListService->process($inventoryItem);
                        }

                        if ($maintenancePlan) {
                            $mData = [
                                'itemId' => $inventoryItem->getId(),
                                'planId' => $maintenancePlan->getId(),
                                'note' => $userNote,
                                'date' => new \DateTime()
                            ];
                            if ($m = $maintenanceService->scheduleMaintenance($mData)) {
                                $maintenanceActions[] = $m;
                            }
                        }
                    }
                }
                $lastItemId = $itemId;
            }

            if ($updatedItems > 0) {
                $this->addFlash('success', "{$updatedItems} item(s) updated OK.");
            }

            // Send an email to the provider of the plan
            if ($maintenancePlan && $maintenancePlan->getProvider() && count($maintenanceActions) > 0) {

                $provider       = $maintenancePlan->getProvider();
                $senderName     = $tenantService->getCompanyName();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $fromEmail      = $tenantService->getSenderEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');
                $toEmail        = $provider->getEmail();

                try {
                    $client = new PostmarkClient($postmarkApiKey);

                    $message = $this->renderView(
                        'emails/maintenance_due.html.twig',
                        [
                            'assignee' => $provider,
                            'maintenance' => $maintenanceActions,
                            'domain' => $tenantService->getAccountDomain()
                        ]
                    );

                    $client->sendEmail(
                        "{$senderName} <{$fromEmail}>",
                        $toEmail,
                        'You have been assigned item(s) for maintenance',
                        $message,
                        null,
                        null,
                        true,
                        $replyToEmail
                    );

                } catch (\Exception $generalException) {
                    $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
                }
            }

            if (count($idArray) > 1) {
                return $this->redirectToRoute('item_list');
            } else {
                return $this->redirectToRoute('item', ['id' => $lastItemId]);
            }

        }

        return $this->render(
            'admin/item/modal_item_move.html.twig',
            array(
                'title' => $modalTitle,
                'idSet' => $idSet,
                'form' => $form->createView()
            )
        );

    }
}
