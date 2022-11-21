<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Event;
use AppBundle\Entity\Setting;
use AppBundle\Entity\Site;
use AppBundle\Form\Type\Settings\OpeningHoursType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class OpeningHoursController extends Controller
{
    /**
     * @Route("admin/site/{siteId}/event/list", requirements={"siteId": "\d+"}, name="opening_hours_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction($siteId)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        /** @var \AppBundle\Services\SettingsService $settingService */
        $settingService = $this->get('settings');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');
        if (!$site = $siteRepo->find($siteId)) {
            $this->addFlash('error', "Site {$siteId} not found");
            return $this->redirectToRoute('home');
        }

        $filter = [
            'site' => $site,
            'type' => ['o', 'c']
        ];
        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');
        $event = $eventRepo->findBy($filter);

        $tableHeader = array(
            'Date',
            '',
            '',
            'From',
            'Changeover',
            'To',
            ''
        );

        // Sort by date asc
        $sorted = [];
        foreach ($event AS $i) {
            $d = $i->getDate()->format("Y-m-d");
            $sorted[$d] = $i;
        }

        ksort($sorted);

        foreach ($sorted AS $i) {
            /** @var $i \AppBundle\Entity\Event */
            if ($i->getType() == 'o') {
                $type = '<span class="label bg-green">Open</span>';
            } else if ($i->getType() == 'e') {
                $type = '<span class="label bg-brown">N/A</span>';
            } else {
                $type = '<span class="label bg-red">Closed</span>';
            }

            $title = $i->getTitle();
            $eventLink = $this->generateUrl('event_admin', ['eventId' => $i->getId()]);
            if ($settingService->getSettingValue('ft_events')) {
                if (!$title) {
                    $title = '-- convert to an event --';
                }
                $eventTitle = '<a href="'.$eventLink.'">'.$title.'</a>';
            } else {
                $eventTitle = $title;
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getDate()->format("l j F Y"),
                    $eventTitle,
                    $type,
                    $i->getTimeFrom(),
                    $i->getTimeChangeover(),
                    $i->getTimeTo(),
                    '<a href="javascript:void(0)" onClick="deleteTableRow(\'Event\', tr'.$i->getId().'); return false;">Delete</a>'
                )
            );
        }

        $modalUrl = $this->generateUrl('opening_hours_admin', ['siteId' => $siteId]);

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About custom opening hours</h4>
Custom opening hours are used to modify your regular weekly hours (which are defined on each site).
<br><br>
If you open at the same time each week, just create time slots when you create/edit a site.
If you have irregular opening hours, then create custom time slots.
You can mix the two, to have regular hours each week, and then create a closed time slot for a holiday.
<br><br>
Custom opening hours in the past will be deleted automatically on a regular basis.
<br><br>
Custom opening hours can be upgraded to full events where you can add attendees, take bookings and more.
Events which are set to allow item pickup and return are shown on this list and will also appear on the item booking calendar.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Custom opening hours : '.$site->getName(),
                'pageTitle'  => 'Custom opening hours : '.$site->getName(),
                'addButtonText' => 'Add new',
                'entityName' => 'Event', // Used in the sort order handler
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => false,
                'noActions' => true,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing sites
     * @Route("admin/site/{siteId}/event", requirements={"siteId": "\d+"}, name="opening_hours_admin")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function addEventAction(Request $request, $siteId)
    {
        $em = $this->getDoctrine()->getManager();

        $event = new Event();
        $d = new \DateTime();
        $event->setDate($d);

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');
        if (!$site = $siteRepo->find($siteId)) {
            $this->addFlash('error', "Site {$siteId} not found");
            return $this->redirectToRoute('home');
        }

        $event->setSite($site);

        $options = [
            'action' => $this->generateUrl('opening_hours_admin', ['siteId' => $siteId])
        ];

        $form = $this->createForm(OpeningHoursType::class, $event, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $d = $form->get('date')->getData();

            if (strpos($d, ' to ')) { // Multiple dates

                $bang = explode(' to ', $d);

                $dFrom = $bang[0];
                $dTo   = $bang[1];

            } else {

                $dFrom = $d;
                $dTo   = $d;

            }

            $dFrom = new \DateTime($dFrom);
            $dTo   = new \DateTime($dTo);

            $diff = (int)$dTo->diff($dFrom)->format('%d');

            for ($i = 0; $i <= $diff; $i++) {

                $date = clone $dFrom;
                $date->modify($i . ' day');

                $event2 = clone $event;

                $event2->setDate($date);
                $event2->setCreatedBy($this->getUser());

                $em->persist($event2);

            }

            $em->flush();

            $this->addFlash('success', 'Saved.');

            // Mark this setup stage as complete
            /** @var $repo \AppBundle\Repository\SettingRepository */
            $settingRepo =  $em->getRepository('AppBundle:Setting');
            if (!$setting = $settingRepo->findOneBy(['setupKey' => 'setup_opening_hours'])) {
                $setting = new Setting();
                $setting->setSetupKey('setup_opening_hours');
            }
            $setting->setSetupValue(1);
            $em->persist($setting);
            $em->flush();

            return $this->redirectToRoute('opening_hours_list', ['siteId' => $siteId]);

        }

        return $this->render(
            'modals/settings/event.html.twig',
            array(
                'title' => 'Add custom hours for '.$site->getName(),
                'subTitle' => 'Add custom hours, or holidays',
                'form' => $form->createView(),
                'today' => $event->getDate()->format("D M d Y") // to initialise the datepicker
            )
        );
    }

}