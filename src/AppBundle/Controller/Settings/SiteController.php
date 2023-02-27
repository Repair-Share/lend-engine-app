<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\Setting;
use AppBundle\Entity\Site;
use AppBundle\Entity\SiteOpening;
use AppBundle\Entity\TenantSite;
use AppBundle\Form\Type\Settings\SiteType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SiteController extends Controller
{
    /**
     * @Route("admin/site/list", name="site_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();
        $sites = $em->getRepository('AppBundle:Site')->findOrderedByName();

        $tableHeader = array(
            'Site name',
            'Active',
            'Default item check-in location',
            'Opening hours (changeover)',
            '',
            ''
        );

        foreach ($sites AS $i) {
            /** @var $i \AppBundle\Entity\Site */

            $name = $i->getName();

            $openingHours = '';

            // sort by day
            $keyed = [];
            foreach ($i->getSiteOpenings() AS $o) {
                /** @var $o \AppBundle\Entity\SiteOpening */
                $keyed[$o->getWeekDay().$o->getTimeFrom()] = $o;
            }
            ksort($keyed);

            foreach ($keyed AS $siteOpening) {
                /** @var $siteOpening \AppBundle\Entity\SiteOpening */
                $openingHours .= '<div>'.$this->weekDay($siteOpening->getWeekDay()).' '.$siteOpening->getTimeFrom().' - '.$siteOpening->getTimeTo();
                if ($tCo = $siteOpening->getTimeChangeover()) {
                    $openingHours .= ' ('.$tCo.')';
                }
                $openingHours .= '</div>';
            }

            $url = $this->generateUrl('opening_hours_list', ['siteId' => $i->getId()]);

            if (!$openingHours) {
                $openingHours = '<div>Edit site to add regular opening hours each week.</div>';
            }
            $openingHours .= '<div><a href="'.$url.'">Add custom hours / holiday</a></div>';

            $defaultCheckInLocationName = 'Not set';
            if ($i->getDefaultCheckInLocation()) {
                $defaultCheckInLocationName = $i->getDefaultCheckInLocation()->getName();
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'class' => $i->getIsActive() ? 'item-active' : 'item-inactive',
                'data' => array(
                    $name,
                    $i->getIsActive() ? 'Yes' : '',
                    $defaultCheckInLocationName,
                    $openingHours,
                    '<span class="label" style="background-color: '.$i->getColour().'">'.$i->getColour().'</span>',
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('site');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About sites, opening hours and changeover time</h4>
Create a 'site' for each of your physical addresses.
Within each site, you can set up locations (such as cupboards, shelves etc).
For each site, you should set the opening hours, which will be used to create time slots for picking up and returning loans and reservations.
<br><br>
<strong>Loans</strong> start when an item is checked out, and end at the changeover time of the selected return date.<br>
<strong>Reservations</strong> start and end at the changeover time of the selected pickup and return dates.<br><br>
If you don't set a changeover time, then loans and reservations run from the <strong>start</strong> of the pickup slot to the <strong>end</strong> of the return slot,
to maximise loan time and help prevent clashes. To allow same-day return and pickup, loans should be returned at the beginning of a slot.
Set your changeover time to the beginning of the day.
<br><br>
If you open at the same time each week, just create time slots when you create/edit a site.
If you have irregular opening hours, then create custom time slots.
You can mix the two, to have regular hours each week, and then create a closed time slot for a holiday.
<br><br>
The site colour is used in the legend on the availability calendar, linked from the bottom of your member site.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Sites',
                'pageTitle'  => 'Sites',
                'addButtonText' => 'Add a site',
                'entityName' => 'Site', // Used in the sort order handler
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => false,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing sites
     * @Route("admin/site/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="site")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function siteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $accountCode = $settingsService->getTenant(false)->getStub();

        if ($id) {
            $site = $this->getDoctrine()->getRepository('AppBundle:Site')->find($id);
            if (!$site) {
                throw $this->createNotFoundException(
                    'No site found for id '.$id
                );
            }
            $modalTitle = 'Edit site "'.$site->getName().'"';
        } else {
            $site = new Site();
            $modalTitle = 'Add a new site';

            // Create a new default location for this site
            $inventoryLocation = new InventoryLocation();
            $inventoryLocation->setName("In stock");
            $inventoryLocation->setIsActive(true);
            $inventoryLocation->setIsAvailable(true);
            $inventoryLocation->setSite($site);

            if (!$site->getCountry()) {
                $site->setCountry($settingsService->getSettingValue('org_country'));
            }

            $opening = new SiteOpening();
            $opening->setSite($site);
            $opening->setTimeFrom('0900');
            $opening->setTimeTo('1700');
            $opening->setWeekDay(1);
            $site->addSiteOpening($opening);

            // Assign it to the site
            $site->setDefaultCheckInLocation($inventoryLocation);

        }

        // The site opening hours before the form data is read
        $originalOpenings = new ArrayCollection();
        if (count($site->getSiteOpenings()) > 0) {
            foreach ($site->getSiteOpenings() as $opening) {
                $originalOpenings->add($opening);
            }
        }

        // Set a colour
        if (!$site->getColour()) {
            $colour = '#'.$this->random_color();
            $site->setColour($colour);
        }

        $options = [
            'em'     => $em,
            'action' => $this->generateUrl('site', array('id' => $id))
        ];

        $form = $this->createForm(SiteType::class, $site, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$id && isset($inventoryLocation)) {
                $em->persist($inventoryLocation);
            }
            $em->persist($site);
            $em->flush();
            $this->addFlash('success', 'Site saved.');

            // Delete any opening hours removed from the site
            foreach ($originalOpenings as $opening) {
                if (false === $site->getSiteOpenings()->contains($opening)) {
                    $em->remove($opening);
                }
            }

            // Validate the opening hours
            if (is_array($site->getSiteOpenings())) {
                foreach ($site->getSiteOpenings() AS $opening) {
                    if (!is_numeric($opening->getTimeFrom())) {
                        $this->addFlash('error', "Value \"".$opening->getTimeFrom()."\" wasn't a valid time, please double check your opening hours.");
                        $em->remove($opening);
                    }
                    if (!is_numeric($opening->getTimeTo())) {
                        $this->addFlash('error', "Value \"".$opening->getTimeTo()."\" wasn't a valid time, please double check your opening hours.");
                        $em->remove($opening);
                    }
                }
            }

            // Set a setting value so we don't have to count sites on each page to determine functionality
            /** @var $settingRepo \AppBundle\Repository\SettingRepository */
            $settingRepo =  $em->getRepository('AppBundle:Setting');
            if (!$setting = $settingRepo->findOneBy(['setupKey' => 'multi_site'])) {
                $setting = new Setting();
                $setting->setSetupKey('multi_site');
            }
            $sites = $this->getDoctrine()->getRepository('AppBundle:Site')->findAll();
            if (count($sites) > 1) {
                $setting->setSetupValue(1);
            } else {
                $setting->setSetupValue(0);
            }
            $em->persist($setting);
            $em->flush();

            // Mark setup of opening hours as complete
            if (!$setting = $settingRepo->findOneBy(['setupKey' => 'setup_opening_hours'])) {
                $setting = new Setting();
                $setting->setSetupKey('setup_opening_hours');
            }
            $setting->setSetupValue(1);
            $em->persist($setting);
            $em->flush();

            // Also update Core (_core DB)
            $settingsService->updateCore($accountCode);

            return $this->redirectToRoute('site_list');
        }

        return $this->render(
            'modals/settings/site.html.twig',
            array(
                'title' => $modalTitle,
                'subTitle' => '',
                'site' => $site,
                'form' => $form->createView(),
            )
        );

    }

    /**
     * @param $number
     * @return mixed
     */
    private function weekDay($number)
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$number];
    }

    private function random_color_part() {
        return str_pad( dechex( mt_rand( 150, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    private function random_color() {
        return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
    }

}