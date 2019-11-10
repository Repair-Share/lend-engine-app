<?php

namespace AppBundle\Controller\Admin\Item\Tabs;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ItemReservationsController extends Controller
{

    /**
     * @Route("admin/item/{itemId}/reservations.html", name="item_reservations_html", requirements={"itemId": "\d+"})
     */
    public function itemReservationsAction($itemId)
    {
        /** @var $bookingService \AppBundle\Services\Booking\BookingService */
        $bookingService = $this->get("service.booking");
        $filter = [
            'item_ids' => [$itemId]
        ];
        $reservations = $bookingService->getBookings($filter);

        return $this->render(
            'admin/item/tabs/item_reservations.html.twig',
            array(
                'reservations' => $reservations,
                'itemId' => $itemId
            )
        );
    }

}