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

        if ($tagId = $request->get('tagId')) {
            $filter['tagIds'] = [ $tagId ];
        }

        $newSortDir = '';
        if ($request->get('sortBy')) {
            $newSortDir = 'DESC';
        }
        // @TODO

        $filter['sortDir'] = 'ASC';

        if ($request->get('show') == 'recent') {
            $filter['sortBy']  = 'item.createdAt';
            $filter['sortDir'] = 'DESC';
        } else if ($request->get('sortBy')) {
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

        $searchResults = $inventoryService->itemSearch($resultsFrom, $length, $filter);
        $products     = $searchResults['data'];
        $totalRecords = $searchResults['totalResults'];

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
