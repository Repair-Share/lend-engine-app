<?php

namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ChangeItemTypeController extends Controller
{
    /**
     * @Route("admin/item/{itemId}/change-type", name="change_item_type")
     */
    public function changeItemType(Request $request, $itemId)
    {
        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        $item = $itemService->find($itemId);

        if ($newType = $request->get('item_type')) {
            if ($itemService->changeItemType($item, $newType)) {
                $this->addFlash('success', "Changed item to {$newType}");
            } else {
                foreach ($itemService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
            }
            return $this->redirectToRoute('item', ['id' => $itemId]);
        }

        $validTypes = $this->getValidTypes($item);

        return $this->render('admin/item/modal_change_item_type.html.twig', [
            'item' => $item,
            'validTypes' => $validTypes
        ]);
    }

    /**
     * Determine what types we can change this item to
     * @param InventoryItem $item
     * @return array
     */
    private function getValidTypes(InventoryItem $item)
    {
        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        $validTypes = [
            InventoryItem::TYPE_STOCK => 'Stock item',
            InventoryItem::TYPE_SERVICE => 'Service',
            InventoryItem::TYPE_LOAN => 'Loan item',
            InventoryItem::TYPE_KIT => 'Kit',
        ];

        // self
        unset($validTypes[$item->getItemType()]);

        switch ($item->getItemType()) {
            case InventoryItem::TYPE_STOCK:
                $qtyInStock = $itemService->getInventory($item);
                if ((int)$qtyInStock != 0) {
                    return [];
                }
                break;
            case InventoryItem::TYPE_LOAN:

                break;
            case InventoryItem::TYPE_KIT:
                if ($item->getComponents()->count() > 0) {
                    return [];
                }
                break;
            case InventoryItem::TYPE_SERVICE:

                break;
        }

        return $validTypes;
    }

}