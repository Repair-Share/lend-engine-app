<?php

namespace AppBundle\Controller\Site;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SiteController
 * @package AppBundle\Controller\Site
 */
class SiteController extends Controller
{
    /**
     * This code feeds the calendar for member site bookings
     * It shows available sites and pickup/return times based on bookings
     * @Route("site-data", name="site_data")
     */
    public function siteDataAction(Request $request)
    {
        $data = [];

        $itemId   = $request->get('itemId');
        $siteId   = $request->get('site');
        $dateFrom = $request->get('start');
        $dateTo   = $request->get('end');

        /** @var $siteRepo \AppBundle\Repository\SiteRepository */
        $siteRepo = $this->getDoctrine()->getRepository('AppBundle:Site');

        /** @var $extraRepo \AppBundle\Repository\OpeningTimeExceptionRepository */
        $extraRepo = $this->getDoctrine()->getRepository('AppBundle:OpeningTimeException');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var $site \AppBundle\Entity\Site */
        // Use the following from the UI if we get users with loads of sites
//        $sites = $siteRepo->findBy(['id' => [1,2,3]]);
        $sites = $siteRepo->findAll();

        foreach ($sites AS $site) {

            // Get the regular opening times
            $openDays = [];
            foreach ($site->getSiteOpenings() AS $opening) {
                /** @var $opening \AppBundle\Entity\SiteOpening */
                $d = $opening->getWeekDay();
                if ($opening->getTimeChangeover()) {
//                    $opening->setTimeChangeover($opening->getTimeTo());
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

            // Users only get to see dates from today
            // Admins get to choose from start of the month
//            $authChecker = $this->get('security.authorization_checker');
//            if ($authChecker->isGranted('ROLE_ADMIN')) {
//                $day = new \DateTime($dateFrom);
//            } else {
                $day = new \DateTime();
            $day->modify("-28 days");
//            }

            // $dateTo is the end of the visible calendar but we need more slots for auto-setting loan end date
            $to  = new \DateTime($dateTo);
            $to->modify("+28 days");

            // Get loan dates to hide available booking days
            $bookings = [];
            if ($itemId) {
                /** @var $reservationService \AppBundle\Services\Booking\BookingService */
                $reservationService = $this->get("service.booking");
                $filter = [
                    'item_ids' => [$itemId],
                    'from'     => new \DateTime($dateFrom),
                    'to'       => new \DateTime($dateTo)
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

            // From the start of the shown calendar, work forwards to modify open hours if there's a booking
            while ($day <= $to) {
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
//                        $changeOver = $slot[2];
//                        if ($changeOver != $slot[1]) {
//                            $start    = $day->format("Y-m-d").' '.$slot[2].':00';
//                        }

                        foreach ($bookings AS $loanRow) {
                            /** @var $loanRow \AppBundle\Entity\LoanRow */

                            // Slots that exist wholly within a booking
                            if ($start >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                                && $end <= $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                                $slotIsValid = false;
                            }
                            // Slots that start while the booking is active
                            // Allowing slots that start at the same time as the end of the booking
                            // (because start has been modified around line 111 for slots with a changeover time)
                            if ($start >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                                && $start < $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                                $slotIsValid = false;
                            }
                            // Slots that end while the booking is active
                            if ($end >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                                && $end <= $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                                $slotIsValid = false;
                            }
                        }

                        if ($slotIsValid == true || 1) {
                            if ($slot[2]) {
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
                'site_id' => $site->getId(),
                'from'    => $dFrom->format("Y-m-d"),
                'to'      => $dTo->format("Y-m-d"),
            ];
            $openingTimeExceptions = $extraRepo->search($filter);

            foreach ($openingTimeExceptions AS $slot) {
                /** @var $slot \AppBundle\Entity\OpeningTimeException */

                $s_start = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeFrom(), 0, 2).':'.substr($slot->getTimeFrom(), 2, 2).':00';
                $s_end   = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeTo(), 0, 2).':'.substr($slot->getTimeTo(), 2, 2).':00';

                if (!$slot->getTimeChangeover()) {
                    $slot->setTimeChangeover($slot->getTimeTo());
                }
                $s_changeover = $slot->getDate()->format("Y-m-d").' '.substr($slot->getTimeChangeover(), 0, 2).':'.substr($slot->getTimeChangeover(), 2, 2).':00';

                // We've set a custom changeover time, use it for the start of the slot
                // This allows same day return and pickup
                if ($slot->getTimeChangeover() != $slot->getTimeTo()) {
                    $s_start = $s_changeover;
                }

                if ($slot->getType() == 'o') {

                    // Remove if there are bookings for it
                    $slotIsValid = true;

                    foreach ($bookings AS $loanRow) {
                        /** @var $loanRow \AppBundle\Entity\LoanRow */

                        // Slots that exist wholly within a booking
                        if ($s_start >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                            && $s_end <= $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                            $slotIsValid = false;
                        }
                        // Slots that start while the booking is active
                        if ($s_start >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                            && $s_start < $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                            $slotIsValid = false;
                        }
                        // Slots that end while the booking is active
                        if ($s_end >= $loanRow->getDueOutAt()->format("Y-m-d H:i:00")
                            && $s_end <= $loanRow->getDueInAt()->format("Y-m-d H:i:00")) {
                            $slotIsValid = false;
                        }
                    }

                    if ($slotIsValid == true) {
                        $data[] = [
                            'siteId' => $site->getId(),
                            'siteName' => $site->getName(),
                            'changeover' => $s_changeover,
                            'title' => 'Additional hours',
//                            'rendering' => 'background',
                            'color' => $site->getColour(),
                            'start'  => $s_start,
                            'end'    => $s_end,
                        ];
                    }

                } else {
                    // Check all opening times loaded so far
                    foreach ($data AS $k => $openingTime) {
                        // if any opening time lies within closed custom slot, remove it
                        if ($openingTime['start'] > $s_start && $openingTime['end'] < $s_end) {
                            unset($data[$k]);
                        }
                    }
                }
            }

//        $openDays = [];
//        foreach ($data AS $k => $event) {
//            $openDays[] = substr($event['start'], 0, 10);
//        }

        // For all remaining days in the view, create a blocked out cell
//        $day = new \DateTime($dateFrom);
//        $to  = new \DateTime($dateTo);
//        while ($day <= $to) {
//            if (in_array($day->format("Y-m-d"), $openDays)) {
//                // We have a slot for this day
//            } else {
//                $data[] = [
//                    'title' => '-',
//                    'start'  => $day->format("Y-m-d"),
//                    'end'    => $day->format("Y-m-d"),
//                    'rendering' => 'background'
//                ];
//            }
//            $day->modify("+1 day");
//        }

        }
        $data = array_values($data);

        return $this->json($data);
    }

}
