<?php

namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ItemListDataController extends Controller
{

    /**
     * JSON responder for DataTables AJAX list
     * @Route("admin/dt/item/list", name="dt_item_list")
     */
    public function itemListData(Request $request)
    {

        $data = array();

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

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

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

            if ($item->getItemType() == InventoryItem::TYPE_STOCK) {
                $available = '<div class="label bg-gray">Stock item</div>';
                $statusHtml = '';
            } else if ($item->getInventoryLocation() && $item->getInventoryLocation()->getId() == 1 && isset($activeLoans[$itemId])) {
                $available = '<div class="label bg-teal">On loan</div>';

                $statusHtml = '<br><br>';
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

            } else if ($item->getInventoryLocation() && $item->getInventoryLocation()->getIsAvailable() == 1) {
                $available = '<div class="label bg-green">Available</div>';
            } else if ($item->getInventoryLocation()) {
                $available = '<div class="label bg-yellow">On hold</div>';
            } else {
                // no location, it's a kit
                $available = '<div class="label bg-gray">Kit</div>';
            }

            $editItemUrl = $this->generateUrl('item', array('id' => $itemId));
            $links = '<li><a href="'.$editItemUrl.'">Edit</a></li>';

            if ($item->getItemType() == InventoryItem::TYPE_LOAN) {
                $copyItemUrl = $this->generateUrl('item_copy', array('id' => $itemId));
                $links .= '<li><a href="'.$copyItemUrl.'">Copy</a></li>';
            }

            // Reservations
            if ($item->getItemType() == InventoryItem::TYPE_LOAN) {
                $reserveItemUrl = $this->generateUrl('member_search', array('itemId' => $itemId, 'new' => 'reservation'));
                $links .= '<li><a href="'.$reserveItemUrl.'">Reserve</a></li>';
            }

            if (!$item->getInventoryLocation() || ($item->getInventoryLocation() && $item->getInventoryLocation()->getId() > 1)) {
                if ($item->getItemType() == InventoryItem::TYPE_LOAN) {
                    $moveUrl = $this->generateUrl('item_move', ['idSet' => $itemId,]);
                    $links .= '<li><a class="modal-link" href="' . $moveUrl . '">Move / Assign</a></li>';
                }
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
                'admin/item/item_mini.html.twig',
                ['item' => $item]
            );

            // Add the standard columns
            $columns[] = '<input type="checkbox" class="row-checkbox" data-id="'.$item->getId().'" name="itemId[]" value="'.$item->getId().'">';
            $columns[] = $itemHtml;

            // Location / assignment
            if ($item->getItemType() == InventoryItem::TYPE_STOCK) {

                $locationHtml = $available;
                $inventory = $itemService->getInventory($item);
                $totalStock = 0;
                foreach ($inventory AS $line) {
                    $totalStock += $line['qty'];
                }

                $stockUrl = $this->generateUrl('inventory_edit', ['id' => $itemId]);
                $locationHtml .= '<br><br><a href="'.$stockUrl.'" class="modal-link">'.$totalStock.' in stock</a>';

            } else if ($item->getInventoryLocation()) {
                $locationName = preg_replace("/ /", '&nbsp;', $item->getInventoryLocation()->getName());
                if ($item->getInventoryLocation()->getId() == 1) {
                    $locationHtml = $available;
                } else if ($this->get('settings')->getSettingValue('multi_site')) {
                    $siteName = preg_replace("/ /", '&nbsp;', $item->getInventoryLocation()->getSite()->getName());
                    $locationHtml = $available .'<div class="item-location"><div>'.$siteName.'</div>'.$locationName.'</div>';
                } else {
                    $locationHtml = $available .'<div class="item-location">'.$locationName.'</div>';
                }
            } else {
                $locationHtml = '-';
            }

            if ($item->getAssignedTo()) {
                $locationHtml .= '<div class="item-location">Assigned to: <br />'.$item->getAssignedTo()->getName().'</div>';
            }
            $columns[] = $locationHtml.$statusHtml;

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