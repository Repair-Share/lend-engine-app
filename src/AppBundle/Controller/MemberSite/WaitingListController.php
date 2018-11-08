<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\WaitingListItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller
 */
class WaitingListController extends Controller
{

    /**
     * @return Response
     * @Route("waiting-list/add/{itemId}", name="waiting_list_add")
     */
    public function waitingListAddAction($itemId)
    {

        $em = $this->getDoctrine()->getManager();

        if (!$this->getUser()) {
            $this->addFlash('error', "Please log in first");
            return $this->redirectToRoute('home');
        }

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        // If browsing as someone else...
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $this->getUser()->getId() != $sessionUserId) {
            $contact = $contactRepo->find($sessionUserId);
        } else {
            $contact = $this->getUser();
        }

        /** @var $inventoryItemRepo \AppBundle\Repository\InventoryItemRepository */
        $inventoryItemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->getDoctrine()->getRepository('AppBundle:WaitingListItem');

        if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
            $this->addFlash('error', "Item {$itemId} not found");
            return $this->redirectToRoute('home');
        }

        $filter = [
            'contact' => $contact,
            'removedAt' => null,
            'inventoryItem' => $inventoryItem
        ];
        if ($waitingListRepo->findOneBy($filter)) {
            $this->addFlash('success', "You're already on the waiting list for this item.");
            return $this->redirectToRoute('public_product', ['productId' => $itemId]);
        }

        /** @var \AppBundle\Entity\WaitingListItem $waitingListItem */
        $waitingListItem = new WaitingListItem();
        $waitingListItem->setContact($contact);
        $waitingListItem->setInventoryItem($inventoryItem);

        $em->persist($waitingListItem);

        try {
            $em->flush();
        } catch (\Exception $generalException) {
            $this->addFlash('error', $generalException->getMessage());
        }

        return $this->redirectToRoute('public_product', ['productId' => $itemId]);
    }

    /**
     * @return Response
     * @Route("waiting-list/remove/{itemId}", name="waiting_list_remove")
     */
    public function waitingListRemoveAction($itemId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $inventoryItemRepo \AppBundle\Repository\InventoryItemRepository */
        $inventoryItemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        // If browsing as someone else...
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $this->getUser()->getId() != $sessionUserId) {
            $contact = $contactRepo->find($sessionUserId);
        } else {
            $contact = $this->getUser();
        }

        if (!$inventoryItem = $inventoryItemRepo->find($itemId)) {
            $this->addFlash('error', "Item {$itemId} not found");
            return $this->redirectToRoute('home');
        }

        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->getDoctrine()->getRepository('AppBundle:WaitingListItem');

        $filter = [
            'contact' => $contact,
            'removedAt' => null,
            'inventoryItem' => $inventoryItem
        ];
        if ($waitingListItem = $waitingListRepo->findOneBy($filter)) {

            $em->remove($waitingListItem);
            try {
                $em->flush();
            } catch (\Exception $generalException) {
                $this->addFlash('error', $generalException->getMessage());
            }

            return $this->redirectToRoute('public_product', ['productId' => $itemId]);
        }

        return $this->redirectToRoute('public_product', ['productId' => $itemId]);

    }

}
