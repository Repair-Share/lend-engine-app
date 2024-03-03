<?php


namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
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

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->container->get('service.contact');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

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
                    if ( $inventoryService->itemMove($inventoryItem, $toLocation, null, $userNote) ) {
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

                /** @var \AppBundle\Entity\Contact $provider */
                $provider      = $maintenancePlan->getProvider();
                $toEmail       = $provider->getEmail();
                $toName        = $provider->getName();

                $token = $contactService->generateAccessToken($provider);
                $loginUri = $tenantService->getTenant(false)->getDomain(true);
                $loginUri .= '/access?t='.$token.'&e='.urlencode($provider->getEmail());
                $loginUri .= '&r=/admin/maintenance/list&assignedTo='.$provider->getId();

                $message = $this->renderView(
                    'emails/maintenance_due.html.twig',
                    [
                        'assignee' => $provider,
                        'maintenance' => $maintenanceActions,
                        'domain' => $tenantService->getAccountDomain(),
                        'loginUri' => $loginUri
                    ]
                );

                // Send the email
                $subject = 'You have been assigned item(s) for maintenance';
                $emailService->send($toEmail, $toName, $subject, $message, true);

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

    /**
     * Modal content for forward pickup confirmation
     * @Route("admin/item/forward-pickup-move-confirm", name="forward_pickup_move_confirm")
     */
    public function forwardPickupMoveConfirmAction(Request $request)
    {
        $itemName = $locationName = $siteName = $error = '';

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\LoanRowRepository */
        $loanRowRepo = $em->getRepository('AppBundle:LoanRow');

        $loanRowID = $request->get('id');

        if (!$loanRowID) {
            $error = 'No id added';
        }

        if (!$error) {

            if (!$loanRowID) {
                $error = "Loan item ID {$loanRowID} does not exist.";
            } else {

                $loanRow = $loanRowRepo->find($loanRowID);

                $itemName     = $loanRow->getInventoryItem()->getName();
                $locationName = $loanRow->getInventoryItem()->getInventoryLocation()->getSite()->getDefaultForwardPickLocation()->getName();
                $siteName     = $loanRow->getInventoryItem()->getInventoryLocation()->getSite()->getName();

            }

        }

        return $this->render(
            'modals/forwardPickup.html.twig',
            array(
                'title'          => 'Confirmation',
                'itemName'       => $itemName,
                'location'       => $locationName,
                'site'           => $siteName,
                'forwardPickUrl' => $this->generateUrl('forward_pickup_move', array('id' => $loanRow->getId())),
                'error'          => $error
            )
        );
    }

    /**
     * Moving item to default the forward pickup location
     * @Route("admin/item/forward-pickup-move", name="forward_pickup_move")
     */
    public function forwardPickupMoveAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\LoanRowRepository */
        $loanRowRepo = $em->getRepository('AppBundle:LoanRow');

        /** @var $loanRepo \AppBundle\Repository\LoanRepository */
        $loanRepo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $error = '';

        $forwardPicking = $settingsService->getSettingValue('forward_picking');

        if (!$forwardPicking) {
            $error = 'Reservation forward picking is not allowed';
        }

        $loanRowID = $request->get('id');

        if (!$loanRowID) {
            $error = 'No id added';
        }

        if (!$error) {

            $loanRow = $loanRowRepo->find($loanRowID);

            if (!$loanRow) {
                $error = "Loan item ID {$loanRowID} does not exist.";
            } else {

                $loanID = $loanRow->getLoan()->getId();

                $loan = $loanRepo->findOneBy(['id' => $loanID]);

                if (!$loan) {
                    $error = "Loan ID {$loanID} does not exist.";
                } elseif ($loan->getStatus() !== Loan::STATUS_RESERVED) {
                    $error = 'Loan is not reserved.';
                } else {

                    if ($loanRow->getInventoryItem()->getInventoryLocation()->getSite() !== $loanRow->getSiteFrom()) {
                        $error = 'Item needs moving from ' . $loanRow->getInventoryItem()->getInventoryLocation()->getSite()->getName();
                    } else {

                        $siteID = $loanRow->getInventoryItem()->getInventoryLocation()->getSite()->getId();

                        // Get the site's default forward pick location
                        $siteRepo = $em->getRepository('AppBundle:Site');

                        $site = $siteRepo->findOneBy([
                            'id' => $siteID
                        ]);

                        if (!$site) {
                            $error = "Site ID {$siteID} does not exist.";
                        } else {

                            if (!$site->getDefaultForwardPickLocation()) {
                                $error = "Site {$siteID} has no default forward pick location.";
                            } else {

                                $itemId = $loanRow->getInventoryItem()->getId();

                                if ($inventoryService->itemMove(
                                    $loanRow->getInventoryItem(),
                                    $site->getDefaultForwardPickLocation()
                                )) {

                                    $loanRow->setItemLocation($site->getDefaultForwardPickLocation());

                                    $em->persist($loanRow);
                                    $em->flush();

                                } else {
                                    $this->addFlash('error', "Item ID {$itemId} cannot be moved - it does not exist.");
                                }

                            }

                        }

                    }

                }

            }

        }

        if ($error) {
            $this->addFlash('error', $error);
        } else {
            $this->addFlash('success', 'Item is moved');
        }

        return $this->redirectToRoute('loan_list', ['status' => 'RESERVED']);
    }

}
