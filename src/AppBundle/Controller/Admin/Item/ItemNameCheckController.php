<?php

namespace AppBundle\Controller\Admin\Item;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemNameCheckController extends Controller
{
    /**
     * Called when creating a new item to see if there's a duplicate name
     * @Route("admin/item/name-check", name="item_name_check")
     */
    public function itemNameCheck(Request $request)
    {
        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');
        $name = $request->get('name');

        if ($itemRepo->findBy(['name' => $name])) {
            return $this->json(1);
        } else {
            return $this->json(0);
        }
    }

}