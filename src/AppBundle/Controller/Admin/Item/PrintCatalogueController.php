<?php

namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\ProductTag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintCatalogueController extends Controller
{

    /**
     * @Route("admin/printable-catalogue", name="printable_catalogue")
     */
    public function printableCatalogueAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        if (!$columns = $request->get('columns')) {
            $columns = 2;
        }

        /** @var \AppBundle\Repository\ProductTagRepository $categoryRepo */
        $categoryRepo = $em->getRepository('AppBundle:ProductTag');

        $data   = [];

        if ($cId = $request->get('category')) {
            $category = $categoryRepo->find($cId);
            $data[] = $this->getCategoryItems($category);
        } else {
            $categories = $categoryRepo->findAllOrderedByName();
            foreach ($categories AS $category) {
                $data[] = $this->getCategoryItems($category);
            }
        }

        $colSize = 12 / $columns;

        return $this->render('item/printable_list.html.twig', array(
            'data' => $data,
            'colSize' => (int)$colSize
        ));

    }

    private function getCategoryItems(ProductTag $category)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        /** @var \AppBundle\Repository\InventoryItemRepository $repo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        $filter['tagIds'] = [ $category->getId() ];

        $searchResults = $inventoryService->itemSearch(0, 200, $filter);
        $products      = $searchResults['data'];

        $defaultLoanDays = (int)$this->get('settings')->getSettingValue('default_loan_days');
        $minLoanDays = (int)$this->get('settings')->getSettingValue('min_loan_days');

        $items = [];
        foreach ($products AS $product) {
            /** @var \AppBundle\Entity\InventoryItem $product */
            $itemId = $product->getId();
            $item = $itemRepo->find($itemId);

            // Calculate the item fee for the full loan period
            $itemFee = $itemService->determineItemFee($product, null);

            // Usually 1, for a daily charge
            if (!$itemLoanDays = $product->getMaxLoanDays()) {
                $itemLoanDays = $defaultLoanDays;
            }

            // Multiply out for the UI
            if ($minLoanDays > $itemLoanDays) {
                $itemFee = $itemFee * $minLoanDays;
                $itemLoanDays = $itemLoanDays * $minLoanDays;
            }

            $item->setLoanFee($itemFee);
            $item->setMaxLoanDays($itemLoanDays);

            $items[] = $item;
        }

        return [
            'name'     => $category->getName(),
            'products' => $items
        ];
    }

}