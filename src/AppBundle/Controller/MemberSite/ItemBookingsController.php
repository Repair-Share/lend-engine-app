<?php

/**
 * Get a list of bookings to feed the jQuery calendar
 */

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ItemBookingsController extends Controller
{
    /**
     * @Route("item/{itemId}/reservations.json", requirements={"itemId": "\d+"}, name="item_reservations_json")
     */
    public function getReservations($itemId, Request $request)
    {
        $data = [];

        $dateFrom = $request->get('start');
        $dateTo   = $request->get('end');

        /** @var $bookingService \AppBundle\Services\Booking\BookingService */
        $bookingService = $this->get("service.booking");

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get("settings");

        // From and To are passed in from the calendar view
        $filter = [
            'item_ids' => [$itemId],
            'from'     => new \DateTime($dateFrom),
            'to'       => new \DateTime($dateTo)
        ];
        $reservations = $bookingService->getBookings($filter);

        foreach ($reservations AS $reservation) {

            /** @var $reservation \AppBundle\Entity\LoanRow */
            $statusName = $reservation->getLoan()->getStatus();
            if ($statusName == 'ACTIVE') {
                $statusName = 'ON LOAN';
            }

            if (in_array($reservation->getLoan()->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                if ($reservation->getCheckedInAt() != null) {
                    // even though the loan is outstanding, the item has been checked in
                    continue;
                }
            }

            // Modify times to match local time for calendar
            $tz = $settingsService->getSettingValue('org_timezone');
            $timeZone = new \DateTimeZone($tz);
            $utc = new \DateTime('now', new \DateTimeZone("UTC"));
            $offSet = $timeZone->getOffset($utc)/3600;

            $i = $reservation->getDueInAt()->modify("{$offSet} hours");
            $reservation->setDueInAt($i);
            $o = $reservation->getDueOutAt()->modify("{$offSet} hours");
            $reservation->setDueOutAt($o);

            // To handle loans
            if (!$reservation->getDueOutAt()) {
                $reservation->setDueOutAt( $reservation->getLoan()->getTimeOut() );
            }

            $title = $reservation->getDueOutAt()->format("jS g:i a").' to '.$reservation->getDueInAt()->format("jS g:i a");

            $data[] = [
                'loanId' => $reservation->getLoan()->getId(),
                'loanTo' => $reservation->getLoan()->getContact()->getName(),
                'contactId' => $reservation->getLoan()->getContact()->getId(),
                'statusName' => $statusName,
                'title'  => $title,
                'start'  => $reservation->getDueOutAt()->format('Y-m-d H:i:s'),
                'end'    => $reservation->getDueInAt()->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json($data);
    }
}