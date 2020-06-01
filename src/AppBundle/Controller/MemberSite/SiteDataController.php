<?php

/**
 * Feeds data to the member site calendars
 *
 */

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SiteController
 * @package AppBundle\Controller\Site
 */
class SiteDataController extends Controller
{
    /**
     * This code feeds the calendar for member site bookings
     * It shows available sites and pickup/return times based on bookings
     * @Route("site-data", name="site_data")
     */
    public function siteDataAction(Request $request)
    {
        $data = [];

        if (!$itemId = $request->get('itemId')) {
            return $this->json($data);
        }

        $dateFrom = $request->get('start');
        $dateTo   = $request->get('end');

        // We can pass in a loan row to allow slots for shortening an existing loan
        // Passed to getBookings
        $loanRowId = $request->get('excludeLoanRowId');

        /** @var $siteRepo \AppBundle\Repository\SiteRepository */
        $siteRepo = $this->getDoctrine()->getRepository('AppBundle:Site');

        /** @var $itemRepo \AppBundle\Repository\InventoryItemRepository */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');
        $item = $itemRepo->find($itemId);

        /** @var $eventService \AppBundle\Services\Event\EventService */
        $eventService = $this->get('service.event');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        // Get loan dates which we use later to hide available booking days
        // Because this item is loaned out. We need data from all sites, no site filter here
        $bookings = [];
        if ($itemId) {
            /** @var $reservationService \AppBundle\Services\Booking\BookingService */
            $reservationService = $this->get("service.booking");
            $filter = [
                'item_ids' => [$itemId],
                'from'     => new \DateTime($dateFrom),
                'to'       => new \DateTime($dateTo),
                'excludeBookingId' => $loanRowId
            ];
            $bookings = $reservationService->getBookings($filter);
        }

        // Modify times to match local time for calendar
        $tz = $settingsService->getSettingValue('org_timezone');
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = $timeZone->getOffset($utc)/3600;

        foreach ($bookings AS $loanRow) {
            /** @var $loanRow \AppBundle\Entity\LoanRow */
            // Modify UTC database times to match local time for calendar
            $i = $loanRow->getDueInAt()->modify("{$offSet} hours");
            $loanRow->setDueInAt($i);
            $o = $loanRow->getDueOutAt()->modify("{$offSet} hours");
            $loanRow->setDueOutAt($o);
        }

        if (is_numeric($request->get('siteId'))) {
            $sites = [$siteRepo->find($request->get('siteId'))];
        } else {
            /** @var $site \AppBundle\Entity\Site */
            $sites = $item->getSites();
            if (count($sites) == 0) {
                $sites = $siteRepo->findBy(['isActive' => true]);
            }
        }

        foreach ($sites AS $site) {

            if ($site->getIsActive() == false) {
                continue;
            }

            $day = new \DateTime();
            $day->modify("-28 days");
            // $dateTo is the end of the visible calendar but we need more slots for auto-setting loan end date
            $to  = new \DateTime($dateTo);
            $to->modify("+28 days");

            // Get the regular opening times
            $openDays = [];
            foreach ($site->getSiteOpenings() AS $opening) {
                /** @var $opening \AppBundle\Entity\SiteOpening */
                $d = $opening->getWeekDay();
                if ($opening->getTimeChangeover()) {
                    $t_changeover = substr($opening->getTimeChangeover(), 0, 2).':'.substr($opening->getTimeChangeover(), 2, 2);
                } else {
                    $t_changeover = null;
                }

                $openDays[$d][] = [
                    substr($opening->getTimeFrom(), 0, 2).':'.substr($opening->getTimeFrom(), 2, 2),
                    substr($opening->getTimeTo(), 0, 2).':'.substr($opening->getTimeTo(), 2, 2),
                    $t_changeover,
                ];
            }

            // From the start of the shown calendar, work forwards to modify open hours if there's a booking
            while ($day <= $to) {
                $debug = '';
                $weekDay = $day->format('N');
                if (isset($openDays[$weekDay])) {
                    // There's at least one slot on this day
                    foreach ($openDays[$weekDay] AS $slot) {

                        $slotIsValid = true;
                        $start = $day->format("Y-m-d").' '.$slot[0].':00';
                        $end   = $day->format("Y-m-d").' '.$slot[1].':00';

                        // Changeover time (stored as time HHmm not UTC)
                        // If no changeover is set, or it's not the same as the end, update the beginning time
                        // Use the changeover time for the start of slots to allow same day return and pickup
                        $changeOver = $slot[2];
                        if ($changeOver != $slot[1] && $changeOver != null) {
                            $start    = $day->format("Y-m-d").' '.$changeOver.':00';
                        }

                        foreach ($bookings AS $loanRow) {
                            /** @var $loanRow \AppBundle\Entity\LoanRow */

                            // Slots that exist wholly within a booking
                            if ($start > $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                                && $end <= $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                                $slotIsValid = false;
                                $debug = 'EXISTS '.$start.' DURING BOOKING ';
                            }
                            // Slots that start while the booking is active
                            // Allowing slots that start at the same time as the end of the booking
                            // (because start has been modified for slots with a changeover time)
                            if ($start > $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                                && $start < $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                                $slotIsValid = false;
                                $debug = 'START '.$start.' DURING BOOKING ';
                            }
                        }

                        if ($slotIsValid == true) {
                            if ($changeOver) {
                                $changeOverTime = $day->format("Y-m-d").' '.$slot[2].':00';
                            } else {
                                $changeOverTime = null;
                            }
                            $data[] = [
                                'siteId' => $site->getId(),
                                'siteName' => $site->getName(),
                                'title'  => $slot[0].' - '.$slot[1],
                                'color' => $site->getColour(),
                                'changeover' => $changeOverTime,
                                'start'  => $start,
                                'end'    => $end,
                            ];
                        }
                    }

                }
                $day->modify("+1 day");
            }

            // Remove any closed periods / Add any extra periods
            $dFrom = new \DateTime($dateFrom);
            $dTo   = new \DateTime($dateTo);
            $filter = [
                'siteId'  => $site->getId(),
                'from'    => $dFrom->format("Y-m-d"),
                'to'      => $dTo->format("Y-m-d"),
            ];
            $results = $eventService->eventSearch(0, 100, $filter);

            foreach ($results['data'] AS $slot) {

                /** @var $slot \AppBundle\Entity\Event */

                $s_start = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeFrom(), 0, 2).':'.substr($slot->getTimeFrom(), 2, 2).':00';
                $s_end   = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeTo(), 0, 2).':'.substr($slot->getTimeTo(), 2, 2).':00';

                // We've set a custom changeover time, use it for the start of the slot
                // This allows same day return and pickup
                if ($slot->getTimeChangeover()) {
                    $s_changeover = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeChangeover(), 0, 2).':'.substr($slot->getTimeChangeover(), 2, 2).':00';
                    $s_start = $s_changeover;
                } else {
                    $s_changeover = null;
                }

                if ($slot->getType() == 'o') {

                    // Remove this custom slot if there are bookings that clash with it
                    $slotIsValid = true;

                    foreach ($bookings AS $loanRow) {
                        /** @var $loanRow \AppBundle\Entity\LoanRow */

                        // Slots that exist wholly within a booking
                        if ($s_start > $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                            && $s_end < $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                            $slotIsValid = false;
                        }
                        // Slots that start while the booking is active
                        if ($s_start > $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                            && $s_start < $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                            $slotIsValid = false;
                        }
                    }

                    if ($slotIsValid == true) {
                        $data[] = [
                            'siteId' => $site->getId(),
                            'siteName' => $site->getName(),
                            'changeover' => $s_changeover,
                            'title' => 'Additional hours',
                            'color' => $site->getColour(),
                            'start'  => $s_start,
                            'end'    => $s_end,
                        ];
                    }

                } else {
                    // Check all opening times loaded so far
                    foreach ($data AS $k => $openingTime) {
                        // If any opening time lies within closed custom slot, remove it
                        if ($openingTime['start'] >= $s_start && $openingTime['end'] <= $s_end) {
                            unset($data[$k]);
                        }
                    }
                }
            }

        }
        $data = array_values($data);

        return $this->json($data);
    }

}
