<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketSaveController
 * @package AppBundle\Controller\MemberSite
 */
class BasketSaveController extends Controller
{
    /**
     * Update prices, or delivery method
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/save", name="basket_save")
     */
    public function basketSaveAction(Request $request)
    {
        $user = $this->getUser();

        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var $basket \AppBundle\Entity\Loan */
        if (!$basket = $basketService->getBasket()) {
            $this->addFlash('error', "Basket not found. Perhaps your session has timed out.");
            return $this->redirectToRoute('home');
        }

        if ($user->hasRole('ROLE_ADMIN')) {
            $rowFees = $request->request->get('row_fee');
            foreach ($basket->getLoanRows() AS $row) {
                /** @var $row \AppBundle\Entity\LoanRow */
                $itemId = $row->getInventoryItem()->getId();
                $rowFee = $rowFees[$itemId];
                $row->setFee($rowFee);
            }
        }

        $reservationFee = $request->request->get('booking_fee');
        $basket->setReservationFee($reservationFee);

        $collectFrom = $request->request->get('collect_from');
        $basket->setCollectFrom($collectFrom);

        if ($basket->getCollectFrom() == "post") {
            $fee = $basketService->calculateShippingFee($basket);
            $basket->setShippingFee($fee);
        } else {
            $basket->setShippingFee(0);
        }

        $basketService->setBasket($basket);

        $this->addFlash('success', "Your basket has been updated.");
        return $this->redirectToRoute('basket_show');
    }
}
