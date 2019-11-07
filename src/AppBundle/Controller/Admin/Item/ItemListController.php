<?php

namespace AppBundle\Controller\Admin\Item;

use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ItemListController extends Controller
{

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/item/list", name="item_list")
     */
    public function itemList(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $sites = $em->getRepository('AppBundle:Site')->findOrderedByName();
        $tags = $em->getRepository('AppBundle:ProductTag')->findAllOrderedByName();

        $customFields = [];
        if ($this->get('service.tenant')->getFeature('ProductField')) {
            /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
            $customFields = $fieldRepo->findAllOrderedBySort();
        }

        /** @var \AppBundle\Repository\ContactRepository $repo */
        $repo = $this->getDoctrine()->getRepository('AppBundle:Contact');
        $contacts = $repo->findAllStaff();

        /** @var \AppBundle\Repository\ItemConditionRepository $repo */
        $repo = $this->getDoctrine()->getRepository('AppBundle:ItemCondition');
        $itemConditions = $repo->findAllOrderedBySort();

        $searchString = $request->get('search');

        // Shortcut to item if we're doing a barcode scan
        if (is_numeric($searchString)) {
            /** @var \AppBundle\Services\InventoryService $inventoryService */
            $inventoryService = $this->get('service.inventory');
            $filter['search'] = $searchString;
            $searchResults = $inventoryService->itemSearch(0, 10, $filter);

            $totalRecords = $searchResults['totalResults'];
            $inventory    = $searchResults['data'];

            if ($totalRecords == 1) {
                $item = $inventory[0];
                return $this->redirectToRoute('item', ['id' => $item->getId()]);
            }
        }

        return $this->render(
            'admin/item/item_list.html.twig',
            array(
                'searchString' => $searchString,
                'customFields' => $customFields,
                'tags' => $tags,
                'sites' => $sites,
                'team' => $contacts,
                'conditions' => $itemConditions
            )
        );
    }

}