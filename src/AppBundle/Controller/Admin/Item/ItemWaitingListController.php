<?php

namespace AppBundle\Controller\Admin\Item;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemWaitingListController extends Controller
{

    /**
     * @Route("admin/waiting_list", name="item_waiting_list")
     */
    public function ItemWaitingListAction(Request $request)
    {
        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->getDoctrine()->getRepository('AppBundle:WaitingListItem');

        $tableRows = array();

        $tableHeader = array(
            'Date added',
            'Item',
            'Code',
            'Member',
            ''
        );

        $filter = [
            'removedAt' => null
        ];
        $waitingListItems = $waitingListRepo->findBy($filter);

        $n = 0;
        foreach ($waitingListItems AS $waitingListItem) {
            /** @var $waitingListItem \AppBundle\Entity\WaitingListItem */

            $removeItemFromWaitingListUrl = $this->generateUrl(
                'remove_from_wait_list',
                [
                    'itemId' => $waitingListItem->getInventoryItem()->getId(),
                    'contactId' => $waitingListItem->getContact()->getId()
                ]
            );

            $tableRows[] = array(
                'id' => $n,
                'data' => array(
                    $waitingListItem->getAddedAt()->format("d M Y g:i a"),
                    $waitingListItem->getInventoryItem()->getName(),
                    $waitingListItem->getInventoryItem()->getSku(),
                    $waitingListItem->getContact()->getName(),
                    '<a href="'.$removeItemFromWaitingListUrl.'">Remove</a>'
                )
            );
            $n++;
        }

        return $this->render('item/item_waiting_list.html.twig', array(
            'pageTitle' => 'Item waiting list',
            'tableHeader' => $tableHeader,
            'tableRows' => $tableRows
        ));
    }

    /**
     * @Route("admin/member/{contactId}/add-to-waiting-list/{itemId}", name="add_to_waiting_list")
     */
    public function addToWaitingList($contactId, $itemId)
    {

        /** @var \AppBundle\Services\WaitingList\WaitingListService $waitingListService */
        $waitingListService = $this->get('service.waiting_list');

        /** @var $inventoryItemRepo \AppBundle\Repository\InventoryItemRepository */
        $inventoryItemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->getDoctrine()->getRepository('AppBundle:Contact');

        if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
            $this->addFlash('error', "Item {$itemId} not found");
            return $this->redirectToRoute('home');
        }

        if (!$contact = $contactRepo->find($contactId)) {
            $this->addFlash('error', "Contact {$contactId} not found");
            return $this->redirectToRoute('home');
        }

        if (!$waitingListService->add($contact, $inventoryItem)) {
            foreach ($waitingListService->errors AS $error) {
                $this->addFlash('error', $error);
            }
        }

        $this->addFlash('success', "Added to waiting list OK.");

        return $this->redirectToRoute('item_waiting_list');
    }

    /**
     * @Route("admin/waiting_list/remove/contact/{contactId}/item/{itemId}", name="remove_from_wait_list")
     */
    public function ItemWaitingListRemoveAction($contactId, $itemId)
    {
        /** @var \AppBundle\Services\WaitingList\WaitingListService $waitingListService */
        $waitingListService = $this->get('service.waiting_list');

        /** @var $inventoryItemRepo \AppBundle\Repository\InventoryItemRepository */
        $inventoryItemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->getDoctrine()->getRepository('AppBundle:Contact');

        if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
            $this->addFlash('error', "Item {$itemId} not found");
            return $this->redirectToRoute('home');
        }

        if (!$contact = $contactRepo->find($contactId)) {
            $this->addFlash('error', "Contact {$contactId} not found");
            return $this->redirectToRoute('home');
        }

        if (!$waitingListService->deleteEntry($contact, $inventoryItem)) {
            foreach ($waitingListService->errors AS $error) {
                $this->addFlash('error', $error);
            }
        }

        $this->addFlash('success', "Removed OK.");

        return $this->redirectToRoute('item_waiting_list');

    }

}