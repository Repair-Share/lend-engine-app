<?php

namespace AppBundle\Controller\Item;

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

        /** @var \AppBundle\Repository\InventoryItemRepository $repo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        $filter['tagIds'] = [ $category->getId() ];

        $searchResults = $inventoryService->itemSearch(0, 200, $filter);
        $products      = $searchResults['data'];

        $items = [];
        foreach ($products AS $product) {
            /** @var \AppBundle\Entity\InventoryItem $product */
            $itemId = $product->getId();
            $item = $itemRepo->find($itemId);
            $items[] = $item;
        }

        return [
            'name'     => $category->getName(),
            'products' => $items
        ];
    }

}