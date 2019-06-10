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

        $count = 0;
        foreach ($idSet AS $itemId) {
            /** @var \AppBundle\Entity\InventoryItem $item */
            $item = $itemRepo->find($itemId);
            switch ($action) {
                case "tag":
                    $tagId = $request->get("batchTag");
                    $tag = $tagRepo->find($tagId);
                    $item->setTags([$tag]);
                    $count++;
                    break;
                case "condition":
                    $conditionId = $request->get("batchCondition");
                    $condition = $conditionRepo->find($conditionId);
                    $item->setCondition($condition);
                    $count++;
                    break;
                case "fee":
                    $fee = $request->get("batchFee");
                    $item->setLoanFee($fee);
                    $count++;
                    break;
                case "period":
                    $period = $request->get("batchPeriod");
                    $item->setMaxLoanDays($period);
                    $count++;
                    break;
            }
            $em->persist($item);
        }

        try {
            $em->flush();
            $this->addFlash("success", "Updated {$count} items.");
        } catch (\Exception $e) {
            $this->addFlash("error", $e->getMessage());
        }

        return $this->redirectToRoute('item_list');
    }

}