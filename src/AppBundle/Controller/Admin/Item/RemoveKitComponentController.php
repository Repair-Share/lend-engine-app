<?php

namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Payment;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\Type\ItemMoveType;
use AppBundle\Form\Type\ItemRemoveType;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

class RemoveKitComponentController extends Controller
{

    /**
     * @Route("admin/item/{itemId}/remove-component/{componentId}", name="remove_component")
     */
    public function itemArchiveAction($itemId, $componentId)
    {
        $em = $this->getDoctrine()->getManager();

        $kitRepo = $em->getRepository('AppBundle:KitComponent');
        $inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');

        $item = $inventoryItemRepo->find($itemId);
        $component = $inventoryItemRepo->find($componentId);

        $kitting = $kitRepo->findOneBy([
            'inventoryItem' => $item,
            'component' => $component
        ]);

        $em->remove($kitting);
        $em->flush();

        $this->addFlash("success", "Removed item OK");

        return $this->redirectToRoute('item', ['id' => $itemId]);
    }

}