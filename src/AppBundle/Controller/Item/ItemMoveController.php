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
     * @Route("admin/item/{id}/move/", name="item_move")
     */
    public function moveAction(Request $request, $id)
    {

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\WaitingList\WaitingListService $waitingListService */
        $waitingListService = $this->get('service.waiting_list');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Entity\InventoryItem $inventoryItem */
        $inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');
        $inventoryItem = $inventoryItemRepo->find($id);

        $options = [
            'em'         => $em,
            'assignedTo' => $inventoryItem->getAssignedTo(),
            'location'   => $inventoryItem->getInventoryLocation(),
            'action'     => $this->generateUrl('item_move', ['id' => $id])
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

            if ( $inventoryService->itemMove($inventoryItem, $toLocation, null, $contact, $userNote, $cost) ) {

                $this->addFlash('success', 'Item location has been updated.');

                $senderName     = $this->get('service.tenant')->getCompanyName();
                $replyToEmail   = $this->get('service.tenant')->getReplyToEmail();
                $fromEmail      = $this->get('service.tenant')->getSetting('from_email');
                $postmarkApiKey = $this->get('service.tenant')->getSetting('postmark_api_key');

                if ($contact) {
                    $toEmail = $contact->getEmail();
                    $user = $this->getUser();
                    try {
                        $client = new PostmarkClient($postmarkApiKey);

                        // Save and switch locale for sending the email
                        $sessionLocale = $this->get('translator')->getLocale();
                        $this->get('translator')->setLocale($contact->getLocale());

                        $message = $this->renderView(
                            'emails/item_assign.html.twig',
                            [
                                'item'        => $inventoryItem,
                                'assignor'    => $user,
                                'assignee'    => $contact,
                                'notes'       => $userNote
                            ]
                        );

                        $client->sendEmail(
                            "{$senderName} <{$fromEmail}>",
                            $toEmail,
                            'You have been assigned "'.$inventoryItem->getName().'"',
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

                if ($toLocation->getIsAvailable() == true) {
                    // Process items that may be on the waiting list
                    $waitingListService->process($inventoryItem);
                }

            }

            return $this->redirectToRoute('public_product', ['productId' => $inventoryItem->getId()]);
        }

        return $this->render(
            'shared/modals/item_move.html.twig',
            array(
                'item' => $inventoryItem,
                'form' => $form->createView()
            )
        );

    }
}
