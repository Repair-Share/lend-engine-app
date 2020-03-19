<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketRemoveItemController
 * @package AppBundle\Controller\MemberSite
 */
class BasketRemoveItemController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/{itemId}/remove", requirements={"itemId": "\d+"}, name="basket_item_remove")
     */
    public function basketItemRemove($itemId)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        if (!$this->getUser()) {
            $this->addFlash('error', "You're not logged in. Please log in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$basket = $basketService->getBasket()) {
            $this->addFlash('error', "No basket found.");
            return $this->redirectToRoute('home');
        }

        foreach ($basket->getLoanRows() AS $row) {
            if ($row->getInventoryItem()->getId() == $itemId) {
                $basket->removeLoanRow($row);
            }
        }

        if ($basket->getCollectFrom() == "post") {
            $fee = $basketService->calculateShippingFee($basket);
            $basket->setShippingFee($fee);
        } else {
            $basket->setShippingFee(0);
        }

        $msg = $this->get('translator')->trans('msg_success.basket_item_removed', [], 'member_site');
        $this->addFlash('success', $msg);

        if (count($basket->getLoanRows()) == 0) {
            $this->get('session')->set('basket', null);
            return $this->redirectToRoute('public_products', ['show' => 'recent']);
        } else {
            $basketService->setBasket($basket);
            return $this->redirectToRoute('basket_show');
        }

    }

}
