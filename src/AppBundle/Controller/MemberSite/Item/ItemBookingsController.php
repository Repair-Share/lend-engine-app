<?php

/**
 * Get a list of bookings to feed the jQuery calendar
 */

namespace AppBundle\Controller\MemberSite\Item;

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

        $bufferHours = (int)$settingsService->getSettingValue('reservation_buffer');

        // From and To are passed in from the calendar view
        $end = new \DateTime($dateTo);
        $filter = [
            'item_ids' => [$itemId],
            'from'     => new \DateTime($dateFrom),
            'to'       => $end->modify("+14 days")
        ];

        // If we have a buffer period, we need to include closed loans to add the buffer
        if ($bufferHours > 0) {
            $filter['statuses'] = ["RESERVED", "ACTIVE", "OVERDUE", "CLOSED"];
        }

        // Get the data
        $reservations = $bookingService->getBookings($filter);

        foreach ($reservations AS $reservation) {

            $color = '#d61702';
            /** @var $reservation \AppBundle\Entity\LoanRow */
            $statusName = $reservation->getLoan()->getStatus();
            if ($statusName == 'ACTIVE') {
                $statusName = 'ON LOAN';
                $color = '#39cccc';
            } else if ($statusName == 'RESERVED') {
                $color = '#ff851b';
            } else if ($statusName == 'CLOSED') {
                $color = '#cccccc';
            }

            if (in_array($reservation->getLoan()->getStatus(), [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])) {
                if ($reservation->getCheckedInAt() != null) {
                    // Even though the loan is open, the item has been checked in
                    // If we have a buffer, we still need to include it
                    if ($bufferHours == 0) {
                        continue;
                    }
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
                'id'     => $reservation->getLoan()->getId(),
                'loanId' => $reservation->getLoan()->getId(),
                'loanTo' => $reservation->getLoan()->getContact()->getName(),
                'contactId' => $reservation->getLoan()->getContact()->getId(),
                'statusName' => $statusName,
                'title'  => $title,
                'color' => $color,
                'start'  => $reservation->getDueOutAt()->format('Y-m-d H:i:s'),
                'end'    => $reservation->getDueInAt()->format('Y-m-d H:i:s'),
            ];

            // Add a buffer period at the start and end of each booking to show on the calendar
            // A 'virtual booking'
            $hours = (int)$settingsService->getSettingValue('reservation_buffer');
            if ($hours > 0) {

                // Add a buffer at the beginning if the loan is not yet running
                if ($statusName != 'CLOSED') {
                    $q1 = clone($reservation->getDueOutAt());
                    $data[] = [
                        'id'     => $reservation->getLoan()->getId(),
                        'loanId' => $reservation->getLoan()->getId(),
                        'loanTo' => '',
                        'contactId' => $reservation->getLoan()->getContact()->getId(),
                        'statusName' => "BUFFER",
                        'title'  => "Quarantine",
                        'color' => "#CCC",
                        'start'  => $q1->modify("-{$hours} hours")->format('Y-m-d H:i:s'),
                        'end'    => $reservation->getDueOutAt()->format('Y-m-d H:i:s'),
                    ];
                }

                // Add a buffer at the end
                $q2 = clone($reservation->getDueInAt());
                $data[] = [
                    'id'     => $reservation->getLoan()->getId(),
                    'loanId' => $reservation->getLoan()->getId(),
                    'loanTo' => '',
                    'contactId' => $reservation->getLoan()->getContact()->getId(),
                    'statusName' => "BUFFER",
                    'title'  => "Quarantine",
                    'color' => "#CCC",
                    'start'  => $reservation->getDueInAt()->format('Y-m-d H:i:s'),
                    'end'    => $q2->modify("+{$hours} hours")->format('Y-m-d H:i:s'),
                ];

            }

        }

        return $this->json($data);
    }
}