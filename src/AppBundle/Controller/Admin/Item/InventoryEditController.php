<?php


namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class InventoryEditController extends Controller
{
    /**
     * @Route("admin/item/{id}/inventory", name="inventory_edit", defaults={"id" = 0})
     */
    public function editInventory(Request $request, $id)
    {
        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $inventoryItemRepo */
        $inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\InventoryLocationRepository $inventoryLocationRepo */
        $inventoryLocationRepo = $em->getRepository('AppBundle:InventoryLocation');

        /** @var \AppBundle\Entity\InventoryItem $inventoryItem */
        $inventoryItem = $inventoryItemRepo->find($id);

        if ($inventoryItem->getItemType() != InventoryItem::TYPE_STOCK) {
            $this->addFlash('error', "This is not a stock item.");
            return $this->redirectToRoute('item', ['id' => $id]);
        }

        // Existing inventory
        $inventory = $itemService->getInventory($inventoryItem);
        $noteText = $request->get('add_note');

        // POSTed data
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
                $change = $qty - $inventoryByLocationId[$locationId];
                if ($change > 0) {
                    if (!$inventoryService->addInventory($inventoryItem->getId(), $change, $locationId, $noteText)) {
                        foreach ($inventoryService->errors AS $error) {
                            $this->addFlash('error', $error);
                        }
                    }
                } else if ($change < 0) {
                    if (!$inventoryService->removeInventory($inventoryItem->getId(), abs($change), $locationId, $noteText)) {
                        foreach ($inventoryService->errors AS $error) {
                            $this->addFlash('error', $error);
                        }
                    }
                }
            }

            $stockUpdated = true;

        }

        // Adding stock to a new location
        $qty = $request->get('add_qty');
        if ($qty > 0 && is_numeric($qty) && $addLocationId > 0) {
            if (!$inventoryService->addInventory($inventoryItem->getId(), $qty, $addLocationId, $noteText)) {
                foreach ($inventoryService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
            }
            $stockUpdated = true;
        }

        if ($stockUpdated == true) {
            if (count($inventoryService->errors) == 0) {
                $this->addFlash('success', "Inventory updated");
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
