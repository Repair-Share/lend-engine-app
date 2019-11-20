<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
     * Update prices
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/save", name="basket_save")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function basketSaveAction(Request $request)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var $basket \AppBundle\Entity\Loan */
        if (!$basket = $basketService->getBasket()) {
            $this->addFlash('error', "Basket not found. Perhaps your session has timed out.");
            return $this->redirectToRoute('home');
        }

        $rowFees = $request->request->get('row_fee');

        foreach ($basket->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */
            $itemId = $row->getInventoryItem()->getId();
            $rowFee = $rowFees[$itemId];
            $row->setFee($rowFee);
        }

        $reservationFee = $request->request->get('booking_fee');
        $basket->setReservationFee($reservationFee);

        $basketService->setBasket($basket);

        $this->addFlash('success', "Saved");
        return $this->redirectToRoute('basket_show');
    }
}
