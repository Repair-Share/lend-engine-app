<?php

namespace AppBundle\Controller\Admin\Item;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ChangeItemTypeController extends Controller
{
    /**
     * @Route("admin/item/{itemId}/change-type", name="change_item_type")
     */
    public function changeItemTypeAction(Request $request, $itemId)
    {
        $em = $this->getDoctrine()->getManager();

        if ($newTypeId = $request->get('typeId')) {

            /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
            $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

            /** @var \AppBundle\Repository\ItemTypeRepository $itemTypeRepo */
            $itemTypeRepo = $this->getDoctrine()->getRepository('AppBundle:ItemType');

            if (!$item = $itemRepo->find($itemId)) {
                $this->addFlash('error', "Item {$itemId} not found");
                return $this->redirectToRoute('item', ['id' => $itemId]);
            }

            if (!$itemType = $itemTypeRepo->find($newTypeId)) {
                $this->addFlash('error', "Item type {$newTypeId} not found");
                return $this->redirectToRoute('item', ['id' => $itemId]);
            }

            $item->setItemType($itemType);
            $em->persist($item);

            try {
                $em->flush();
                $this->addFlash('success', "Item type updated OK");
            } catch (\Exception $e) {
                $this->addFlash('error', "Item type failed to update: ".$e->getMessage());
            }

            return $this->redirectToRoute('item', ['id' => $itemId]);
        }

        return $this->render('default/itemType.html.twig', [
            'itemId' => $itemId
        ]);
    }

}