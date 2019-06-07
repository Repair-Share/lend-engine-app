<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles all the pages on the static marketing site
 * Class SiteController
 * @package AppBundle\Controller
 */
class ItemListController extends Controller
{

    /**
     * @Route("products.{_format}", defaults={"_format": "html"}, name="public_products")
     */
    public function listProductsAction(Request $request, $_format)
    {

        if ($this->get('settings')->getSettingValue('site_is_private')
            && !$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            $msg = $this->get('translator')->trans("public_misc.log_in_first", [], 'member_site');
            $this->addFlash("error", $msg);
            return $this->redirectToRoute('home');
        }

        $pageSize = 50;

        if (!$resultsFrom = $request->get('f')) {
            $resultsFrom = 0;
        }
        $resultsTo = $resultsFrom + $pageSize;

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        // If browsing as someone else, use their prices
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $this->getUser()->getId() != $sessionUserId) {
            $contact = $contactRepo->find($sessionUserId);
        } else {
            $contact = $this->getUser();
        }

        $defaultLoanDays = (int)$this->get('settings')->getSettingValue('default_loan_days');
        $minLoanDays = (int)$this->get('settings')->getSettingValue('min_loan_days');
        $groupItemsWithSameName = (int)$this->get('settings')->getSettingValue('group_similar_items');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');

        $filter = [];
        if ($searchString = $request->get('search')) {
            $filter['search'] = $searchString;
        }

        if ($barcode = $request->get('barcode')) {
            $filter['barcode'] = $barcode;
        }

        if ($tagId = $request->get('tagId')) {
            $filter['tagIds'] = [ $tagId ];
        }

        $newSortDir = '';
        if ($request->get('sortBy')) {
            $newSortDir = 'DESC';
        }

        $filter['sortDir'] = 'ASC';

        if ($request->get('show') == 'recent') {
            $filter['sortBy']  = 'item.createdAt';
            $filter['sortDir'] = 'DESC';
        } else if ($request->get('sortBy')) {
            // @TODO remove the direct injection of sort SQL info via URL
            $filter['sortBy']  = $request->get('sortBy');
            if ($request->get('sortDir')) {
                $filter['sortDir'] = $request->get('sortDir');
            } else {
                $filter['sortDir'] = 'ASC';
            }
        }

        if ($siteId = $request->get('siteId')) {
            $filter['siteId'] = $siteId;
        }

        // If not admin, only show the public items
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $filter['showOnline'] = true;
        }

        $length = $resultsTo - $resultsFrom;

        // Store how many of each item we have, keyed by name
        $itemQuantity = [];
        $itemQuantityAvailable = [];

        if ($groupItemsWithSameName && !$request->get('see_variations')) {
            // Get ALL item IDs that match the filters
            $filter['grouped'] = true;
            $arrayOfItems = $inventoryService->itemSearch(0, 1000, $filter);
            unset($filter['grouped']);

            dump($arrayOfItems);
            $itemsKeyedByName = [];

            foreach ($arrayOfItems['data'] AS $item) {

                // Lowercase the key
                $itemName = strtolower($item['name']);

                if (!isset($itemQuantity[$itemName])) {
                    $itemQuantity[$itemName] = 1;
                    $itemQuantityAvailable[$itemName] = 0;
                }

                if ($item['isAvailable']) {
                    $itemQuantityAvailable[$itemName]++;
                }

                if (isset($itemsKeyedByName[$itemName])) {
                    // we already have one of these. Increment the quantity which we'll set on the item object later
                    $itemQuantity[$itemName]++;
                    // replace only if this one is available
                    if ($item['isAvailable']) {
                        $itemsKeyedByName[$itemName] = $item['id'];
                    }
                } else {
                    $itemsKeyedByName[$itemName] = $item['id'];
                }
            }

            // Run the search again only with the chosen IDs
            $filter['idSet'] = array_values($itemsKeyedByName);
            $searchResults = $inventoryService->itemSearch($resultsFrom, $length, $filter);
        } else {
            if ($request->get('see_variations')) {
                $filter['exactNameMatch'] = true;
            }
            // Return an array of objects
            $searchResults = $inventoryService->itemSearch($resultsFrom, $length, $filter);
        }

        $products     = $searchResults['data'];
        $totalRecords = $searchResults['totalResults'];

        // Go straight to item if we're scanning a barcode or there's only one result
        if ($totalRecords == 1 && is_numeric($searchString)) {
            $item = $products[0];
            return $this->redirectToRoute('public_product', ['productId' => $item->getId()]);
        }

        // Turn into array of objects
        $items = [];
        foreach ($products AS $item) {
            /** @var \AppBundle\Entity\InventoryItem $item */

            $itemFee = $itemService->determineItemFee($item, $contact);

            // Usually 1, for a daily charge
            if (!$itemLoanDays = $item->getMaxLoanDays()) {
                $itemLoanDays = $defaultLoanDays;
            }

            // Multiply out for the UI
            if ($minLoanDays > $itemLoanDays) {
                $itemFee = $itemFee * $minLoanDays;
                $itemLoanDays = $itemLoanDays * $minLoanDays;
            }

            $item->setLoanFee($itemFee);
            $item->setMaxLoanDays($itemLoanDays);

            if ($request->get('see_variations') || !$groupItemsWithSameName) {
                $item->setQuantity(1);
                $item->setQuantityAvailable(1);
            } else {
                $item->setQuantity($itemQuantity[strtolower($item->getName())]);
                $item->setQuantityAvailable($itemQuantityAvailable[strtolower($item->getName())]);
            }

            $items[] = $item;

        }

        $filterSites = [];
        $filterSites[] = [
            'id' => null,
            'name' => $this->get('translator')->trans("public_misc.any_site", [], 'member_site')
        ];
        $sites = $siteRepo->findBy(['isActive' => true]);
        foreach ($sites AS $site) {
            /** @var \AppBundle\Entity\Site $site */
            $filterSites[] = [
                'id' => $site->getId(),
                'name' => $site->getName()
            ];
        }
        $itemFilter = [
            'sites' => $filterSites
        ];

        $pages = [];
        $pages[] = [
            'f' => 0,
            't' => $pageSize
        ];
        $to = $pageSize;
        while ($to < $totalRecords) {
            $pages[] = [
                'f' => $to,
                't' => $to + $pageSize
            ];
            $to += $pageSize;
        }

        if ($resultsTo > $totalRecords) {
            $resultsTo = $totalRecords;
        }

        if ($request->get('e')) {
            $template = 'member_site/items_embedded.html.twig';
        } else {
            $template = 'member_site/items.html.twig';
        }

        return $this->render($template, array(
            'products' => $items,
            'totalRecords' => $totalRecords,
            'from'     => $resultsFrom + 1,
            'to'       => $resultsTo,
            'pages'    => $pages,
            'filter'   => $itemFilter,
            'user'     => $contact,
            'sortDir'  => $newSortDir
        ));

    }

}
