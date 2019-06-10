<?php


namespace AppBundle\Controller\Item;

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
            $cost       = $form->get('cost')->getData();

            if (!$contact = $form->get('contact')->getData()) {
                $contact = null;
            }

            $assignedItemNames = '';
            $updatedItems = 0;

            foreach ($idArray AS $itemId) {
                if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
                    $this->addFlash("error", "Item ID {$itemId} cannot be moved - it does not exist.");
                    continue;
                } else {
                    if ( $inventoryService->itemMove($inventoryItem, $toLocation, null, $contact, $userNote, $cost) ) {
                        $updatedItems++;
                        $assignedItemNames .= '<div>- '.$inventoryItem->getName().'</div>';
                        if ($toLocation->getIsAvailable() == true) {
                            // Process items that may be on the waiting list
                            $waitingListService->process($inventoryItem);
                        }
                    }
                }
            }

            if ($updatedItems > 0) {
                $this->addFlash('success', "{$updatedItems} item(s) updated OK.");
            }

            // Send an email to the new assignee
            if ($contact && $assignedItemNames) {

                $senderName     = $tenantService->getCompanyName();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $fromEmail      = $tenantService->getSenderEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');
                $toEmail        = $contact->getEmail();
                $user           = $this->getUser();

                try {
                    $client = new PostmarkClient($postmarkApiKey);

                    // Save and switch locale for sending the email
                    $sessionLocale = $this->get('translator')->getLocale();
                    $this->get('translator')->setLocale($contact->getLocale());

                    $message = $this->renderView(
                        'emails/item_assign.html.twig',
                        [
                            'itemNames'       => $assignedItemNames,
                            'newLocationName' => $toLocation->getSite()->getName().': '.$toLocation->getName(),
                            'assignor'    => $user,
                            'assignee'    => $contact,
                            'notes'       => $userNote
                        ]
                    );

                    $client->sendEmail(
                        "{$senderName} <{$fromEmail}>",
                        $toEmail,
                        'You have been assigned item(s)',
                        $message,
                        null,
                        null,
                        true,
                        $replyToEmail
                    );

                    // Revert locale for the UI
                    $this->get('translator')->setLocale($sessionLocale);
                    $this->addFlash('success', "An email was sent to ".$contact->getName());

                } catch (\Exception $generalException) {
                    $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
                }
            }

            return $this->redirectToRoute('item_list');
        }

        return $this->render(
            'shared/modals/item_move.html.twig',
            array(
                'title' => $modalTitle,
                'idSet' => $idSet,
                'form' => $form->createView()
            )
        );

    }
}
