<?php

namespace AppBundle\Controller\Item;

use AppBundle\Entity\ProductTag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

class BatchUpdateItemController extends Controller
{
    /**
     * @Route("admin/item/batch-update", name="batch_update_item")
     */
    public function batchUpdateItem(Request $request)
    {
        $action = $request->get('batch-option');
        $idSet  = $request->get('itemId');

        if (count($idSet) == 0) {
            $this->addFlash("error", "No items selected.");
            return $this->redirectToRoute('item_list');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var $itemRepo \AppBundle\Repository\InventoryItemRepository */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var $tagRepo \AppBundle\Repository\ProductTagRepository */
        $tagRepo = $em->getRepository('AppBundle:ProductTag');

        /** @var $conditionRepo \AppBundle\Repository\ItemConditionRepository */
        $conditionRepo = $em->getRepository('AppBundle:ItemCondition');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        $count = 0;
        foreach ($idSet AS $itemId) {
            /** @var \AppBundle\Entity\InventoryItem $item */
            $item = $itemRepo->find($itemId);
            switch ($action) {
                case "tag":
                    $tagId = $request->get("batchTag");
                    $tag = $tagRepo->find($tagId);
                    $item->setTags([$tag]);
                    $em->persist($item);
                    $count++;
                    break;
                case "condition":
                    $conditionId = $request->get("batchCondition");
                    $condition = $conditionRepo->find($conditionId);
                    $item->setCondition($condition);
                    $em->persist($item);
                    $count++;
                    break;
                case "fee":
                    $fee = $request->get("batchFee");
                    $item->setLoanFee($fee);
                    $em->persist($item);
                    $count++;
                    break;
                case "period":
                    $period = $request->get("batchPeriod");
                    $item->setMaxLoanDays($period);
                    $em->persist($item);
                    $count++;
                    break;
                case "delete":
                    if ( $inventoryService->itemRemove($item, "Batch deleted") ) {
                        $count++;
                    } else {
                        $this->addFlash('error', "Failed to delete item:".$item->getId());
                    }
                    break;
            }
        }

        try {
            $em->flush();
            $this->addFlash("success", "Updated {$count} items.");
        } catch (\Exception $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('item_list', ['search' => $request->get('searchBox')]);
    }

}