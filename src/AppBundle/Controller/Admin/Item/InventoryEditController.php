<?php


namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Note;
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

class InventoryEditController extends Controller
{
    /**
     * @Route("admin/item/{id}/inventory", name="inventory_edit", defaults={"id" = 0})
     */
    public function editInventory(Request $request, $id)
    {

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        $user = $this->getUser();

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $inventoryItemRepo */
        $inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\InventoryLocationRepository $inventoryLocationRepo */
        $inventoryLocationRepo = $em->getRepository('AppBundle:InventoryLocation');

        $inventoryItem = $inventoryItemRepo->find($id);

        // Existing inventory
        $inventory = $itemService->getInventory($inventoryItem);
        $noteText = $request->get('add_note');

        // POSTed data
        $qty = $request->get('add_qty');
        $addLocationId = $request->get('add_location');
        $quantities = $request->get('quantity');

        $stockUpdated = false;

        if (isset($quantities) && count($quantities) > 0) {

            // Map to location/qty
            $inventoryByLocationId = [];
            foreach ($inventory AS $i) {
                $inventoryByLocationId[$i['locationId']] = $i['qty'];
            }

            // Calculate changes
            foreach ($quantities AS $locationId => $qty) {

                if ($qty < 0) {
                    $this->addFlash('error', "Negative inventory is not allowed.");
                    continue;
                }

                $location = $inventoryLocationRepo->find($locationId);

                $change = $qty - $inventoryByLocationId[$locationId];

                if ($change != 0) {
                    $movement = new ItemMovement();
                    $movement->setInventoryItem($inventoryItem);
                    $movement->setCreatedBy($user);
                    $movement->setQuantity($change);
                    $movement->setInventoryLocation($location);
                    $em->persist($movement);

                    $note = new Note();
                    $note->setCreatedBy($user);
                    $note->setInventoryItem($inventoryItem);
                    if ($change > 0) {
                        $note->setText('Added ' . $change . ' to <strong>' . $location->getSite()->getName() . ' / ' . $location->getName() . '</strong>. ' . $noteText);
                    } else {
                        $note->setText('Removed ' . -$change . ' from <strong>' . $location->getSite()->getName() . ' / ' . $location->getName() . '</strong>. ' . $noteText);
                    }
                    $em->persist($note);
                }

            }

            $stockUpdated = true;

        }

        // Adding stock to a new location
        if ($qty > 0 && is_numeric($qty) && $addLocationId > 0) {
            $location = $inventoryLocationRepo->find($addLocationId);

            $movement = new ItemMovement();
            $movement->setInventoryItem($inventoryItem);
            $movement->setCreatedBy($user);
            $movement->setQuantity($qty);
            $movement->setInventoryLocation($location);
            $em->persist($movement);

            $note = new Note();
            $note->setCreatedBy($user);
            $note->setText('Added '.$qty.' to <strong>'.$location->getSite()->getName().' / '.$location->getName().'</strong>. '.$noteText);
            $note->setInventoryItem($inventoryItem);
            $em->persist($note);

            $stockUpdated = true;
        }

        if ($stockUpdated == true) {
            try {
                $em->flush();
                $this->addFlash('success', "Inventory updated");
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
            return $this->redirectToRoute('item', ['id' => $inventoryItem->getId()]);
        }

        $locations = $inventoryLocationRepo->findOrderedByName('notOnLoan');

        return $this->render(
            'admin/item/modal_inventory_edit.html.twig', [
                'title' => '',
                'item' => $inventoryItem,
                'inventory' => $inventory,
                'locations' => $locations
            ]
        );

    }
}
