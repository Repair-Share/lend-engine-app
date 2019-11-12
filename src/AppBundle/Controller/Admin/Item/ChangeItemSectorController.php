<?php

namespace AppBundle\Controller\Admin\Item;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ChangeItemSectorController extends Controller
{
    /**
     * @Route("admin/item/{itemId}/change-sector", name="change_item_sector")
     */
    public function changeItemSectorAction(Request $request, $itemId)
    {
        $em = $this->getDoctrine()->getManager();

        if ($newTypeId = $request->get('sectorId')) {

            /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
            $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

            /** @var \AppBundle\Repository\ItemSectorRepository $itemTypeRepo */
            $itemTypeRepo = $this->getDoctrine()->getRepository('AppBundle:ItemSector');

            if (!$item = $itemRepo->find($itemId)) {
                $this->addFlash('error', "Item {$itemId} not found");
                return $this->redirectToRoute('item', ['id' => $itemId]);
            }

            if (!$itemType = $itemTypeRepo->find($newTypeId)) {
                $this->addFlash('error', "Item type {$newTypeId} not found");
                return $this->redirectToRoute('item', ['id' => $itemId]);
            }

            $item->setItemSector($itemType);
            $em->persist($item);

            try {
                $em->flush();
                $this->addFlash('success', "Item type updated OK");
            } catch (\Exception $e) {
                $this->addFlash('error', "Item type failed to update: ".$e->getMessage());
            }

            return $this->redirectToRoute('item', ['id' => $itemId]);
        }

        return $this->render('admin/item/item_sector.html.twig', [
            'itemId' => $itemId
        ]);
    }

}