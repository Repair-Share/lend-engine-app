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
    public function listAction(Request $request)
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
            'item/item_list.html.twig',
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

    /**
     * JSON responder for DataTables AJAX list
     * @Route("admin/dt/item/list", name="dt_item_list")
     */
    public function inventoryListAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        $data = array();

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        /** @var $reservationService \AppBundle\Services\Booking\BookingService */
        $reservationService = $this->get("service.booking");

        $defaultLoanFee  = $this->get('settings')->getSettingValue('default_loan_fee');
        $defaultLoanDays = (int)$this->get('settings')->getSettingValue('default_loan_days');

        $draw = $request->get('draw');

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        if ($request->get('filterTagIds')) {
            $filter['tagIds'] = $request->get('filterTagIds');
        }
        if ($request->get('filterLocationId')) {
            $filter['locationId'] = $request->get('filterLocationId');
        }
        if ($request->get('filterAssignedTo')) {
            $filter['assignedTo'] = $request->get('filterAssignedTo');
        }
        if ($request->get('filterCondition')) {
            $filter['itemCondition'] = $request->get('filterCondition');
        }
        if ($request->get('customFieldId') && $request->get('customFieldValue')) {
            $filter['customFieldId'] = $request->get('customFieldId');
            $filter['customFieldValue'] = $request->get('customFieldValue');
        }

        $customFields = [];
        if ($this->get('service.tenant')->getFeature('ProductField')) {
            /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');

            /** @var \AppBundle\Repository\ProductFieldSelectOptionRepository $fieldOptionRepo */
            $fieldOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ProductFieldSelectOption');

            $customFields = $fieldRepo->findAllOrderedBySort();
        }

        /***** THE MAIN QUERY ******/
        $searchResults = $inventoryService->itemSearch($start, $length, $filter);
        $totalRecords = $searchResults['totalResults'];
        $inventory    = $searchResults['data'];

        /** @var \AppBundle\Entity\InventoryItem $item */
        $itemIds = [];
        foreach ($inventory AS $item) {
            $itemIds[] = $item->getId();
        }

        // Find the items on loan
        $onLoanFilter = [
            'item_ids' => $itemIds,
            'statuses' => ['ACTIVE', 'OVERDUE']
        ];
        $activeLoanRows = $inventoryService->getItemsOnLoan($onLoanFilter);
        $activeLoans = [];
        foreach ($activeLoanRows AS $loanRow) {
            /** @var $loanRow \AppBundle\Entity\LoanRow */
            $loanItemId = $loanRow->getInventoryItem()->getId();
            $activeLoans[$loanItemId] = $loanRow;
        }

        // Find the items reserved but not yet collected
        $reservedFilter = [
            'item_ids' => $itemIds,
            'current'  => true
        ];
        $currentReservations = [];
        $reservationLoanRows = $reservationService->getBookings($reservedFilter);
        foreach ($reservationLoanRows AS $reservation) {
            /** @var $reservation \AppBundle\Entity\LoanRow */
            $reservationItemId = $reservation->getInventoryItem()->getId();
            $currentReservations[$reservationItemId] = $reservation;
        }

        foreach ($inventory AS $item) {

            // New set of columns for a new item
            $columns = [];
            $statusHtml = '';
            $itemId = $item->getId();

            if ($item->getInventoryLocation()->getId() == 1 && isset($activeLoans[$itemId])) {
                $available = '<div class="label bg-teal">On loan</div>';

                $statusHtml = 'On loan to ';
                $loanRow = $activeLoans[$itemId];
                $onLoanFromDate = $loanRow->getCheckedOutAt()->format("d M");
                $onLoanToDate   = $loanRow->getDueInAt()->format("d M");
                $loanUrl = $this->generateUrl('public_loan', array('loanId' => $loanRow->getLoan()->getId()));
                $statusHtml .= '<div class="item-reservations"><a href="'.$loanUrl.'">'.$loanRow->getLoan()->getContact()->getName().'</a></div>';
                $statusHtml .= '<div class="item-reservations">'.$onLoanFromDate.' to '.$onLoanToDate.'</div>';
                if ( $loanRow->getDueInAt() < new \DateTime() ) {
                    $statusHtml .= '<div class="label bg-red">OVERDUE</div>';
                }

            } else if (isset($currentReservations[$itemId])) {
                $available = '<div class="label bg-orange">Reserved</div>';

                $reservation = $currentReservations[$itemId];
                $statusHtml = 'Reserved by ';

                $reservedFromDate = $reservation->getDueOutAt()->format("d M");
                $reservedToDate   = $reservation->getDueInAt()->format("d M");
                $loanUrl = $this->generateUrl('public_loan', array('loanId' => $reservation->getLoan()->getId()));
                $statusHtml .= '<div class="item-reservations"><a href="'.$loanUrl.'">'.$reservation->getLoan()->getContact()->getName().'</a></div>';
                $statusHtml .= '<div class="item-reservations">'.$reservedFromDate.' to '.$reservedToDate.'</div>';

            } else if ($item->getInventoryLocation()->getIsAvailable() == 1) {
                $available = '<div class="label bg-green">Available</div>';
            } else {
                $available = '<div class="label bg-yellow">On hold</div>';
            }

            $editItemUrl = $this->generateUrl('item', array('id' => $itemId));
            $links = '<li><a href="'.$editItemUrl.'">Edit</a></li>';

            $copyItemUrl = $this->generateUrl('item_copy', array('id' => $itemId));
            $links .= '<li><a href="'.$copyItemUrl.'">Copy</a></li>';

            // Reservations
            $reserveItemUrl = $this->generateUrl('member_search', array('itemId' => $itemId, 'new' => 'reservation'));
            $links .= '<li><a href="'.$reserveItemUrl.'">Reserve</a></li>';

            if ($item->getInventoryLocation()->getId() > 1) {
                $moveUrl = $this->generateUrl('item_move', ['idSet' => $itemId,]);
                $links .= '<li><a class="modal-link" href="'.$moveUrl.'">Move / Assign</a></li>';

                $removeUrl = $this->generateUrl('item_archive', ['id' => $itemId]);
                $links .= '<li><a class="modal-link" href="'.$removeUrl.'">Delete</a></li>';
            }

            $linkHtml = '
<div class="dropdown">
  <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">Action
  <span class="caret"></span></button>
  <ul class="dropdown-menu pull-right">
    '.$links.'
  </ul>
</div>
<div class="entity-id" style="padding:4px;">ID: '.str_pad($itemId, 4, "0", STR_PAD_LEFT).'</div>';

            /** @var \AppBundle\Entity\InventoryItem $item */

            if (!$item->getLoanFee()) {
                $item->setLoanFee($defaultLoanFee);
            }

            if (!$item->getMaxLoanDays()) {
                $item->setMaxLoanDays($defaultLoanDays);
            }

            $itemHtml = $this->renderView(
                'item/item_mini.html.twig',
                array(
                    'item' => $item
                )
            );

            // Add the standard columns
            $columns[] = '<input type="checkbox" class="row-checkbox" data-id="'.$item->getId().'" name="itemId[]" value="'.$item->getId().'">';
            $columns[] = $itemHtml;

            // Location / assignment
            $locationName = preg_replace("/ /", '&nbsp;', $item->getInventoryLocation()->getName());

            if ($item->getInventoryLocation()->getId() == 1) {
                $locationHtml = $available;
            } else if ($this->get('settings')->getSettingValue('multi_site')) {
                $siteName = preg_replace("/ /", '&nbsp;', $item->getInventoryLocation()->getSite()->getName());
                $locationHtml = $available .'<div class="item-location"><div>'.$siteName.'</div>'.$locationName.'</div>';
            } else {
                $locationHtml = $available .'<div class="item-location">'.$locationName.'</div>';
            }

            if ($item->getAssignedTo()) {
                $locationHtml .= '<div class="item-location">Assigned to: <br />'.$item->getAssignedTo()->getName().'</div>';
            }
            $columns[] = $locationHtml;

            $columns[] = $statusHtml;

            // Add extra columns for selected custom fields
            $customFieldValues = $item->getFieldValues();

            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ProductField */
                if ($field->getShowOnItemList() != true) {
                    continue;
                }
                $fieldId   = $field->getId();
                if (isset($customFieldValues[$fieldId])) {
                    /** @var \AppBundle\Entity\ProductFieldValue $productFieldValue */
                    $productFieldValue = $customFieldValues[$fieldId];
                    if ($field->getType() == 'choice' && $optionId = $productFieldValue->getFieldValue()) {
                        $productFieldSelectOptionName = $fieldOptionRepo->find($optionId)->getOptionName();
                        $columns[] = $productFieldSelectOptionName;
                    } else if ($field->getType() == 'multiselect' && $optionIdString = $productFieldValue->getFieldValue()) {
                        $optionIds = explode(',', $optionIdString);
                        $itemFieldSelectOptionNames = [];
                        foreach ($optionIds AS $optionId) {
                            if ($op = $fieldOptionRepo->find($optionId)) {
                                $itemFieldSelectOptionNames[] = $op->getOptionName();
                            } else {
                                $itemFieldSelectOptionNames[] = "";
                            }
                        }
                        $columns[] = implode(', ', $itemFieldSelectOptionNames);
                    } else if ($field->getType() == 'checkbox') {
                        if ($productFieldValue->getFieldValue() == 1) {
                            $columns[] = 'Yes';
                        } else {
                            $columns[] = '';
                        }
                    } else {
                        $columns[] = $productFieldValue->getFieldValue();
                    }
                } else {
                    $columns[] = '';
                }
            }

            $columns[] = $linkHtml;

            $data[] = $columns;
        }

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $totalRecords,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

}