<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\OpeningTimeException;
use AppBundle\Entity\Setting;
use AppBundle\Entity\Site;
use AppBundle\Form\Type\Settings\OpeningTimeExceptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class OpeningTimeExceptionController extends Controller
{
    /**
     * @Route("admin/site/{siteId}/opening-time-exception/list", requirements={"siteId": "\d+"}, name="opening_time_exception_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction($siteId)
    {

        $tableRows = array();

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        // Get from the DB
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');
        if (!$site = $siteRepo->find($siteId)) {
            $this->addFlash('error', "Site {$siteId} not found");
            return $this->redirectToRoute('home');
        }

        $filter = ['site' => $site];
        $openingRepo = $em->getRepository('AppBundle:OpeningTimeException');
        $opening = $openingRepo->findBy($filter);

        $tableHeader = array(
            'Date',
            '',
            'From',
            'Changeover',
            'To',
            ''
        );

        // Sort by date asc
        $sorted = [];
        foreach ($opening AS $i) {
            $d = $i->getDate()->format("Y-m-d");
            $sorted[$d] = $i;
        }

        ksort($sorted);

        foreach ($sorted AS $i) {
            /** @var $i \AppBundle\Entity\OpeningTimeException */
            if ($i->getType() == 'o') {
                $type = '<span class="label bg-green">Open</span>';
            } else {
                $type = '<span class="label bg-red">Closed</span>';
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $i->getDate()->format("l j F Y"),
                    $type,
                    $i->getTimeFrom(),
                    $i->getTimeChangeover(),
                    $i->getTimeTo(),
                    '<a href="javascript:void(0)" onClick="deleteTableRow(\'OpeningTimeException\', tr'.$i->getId().'); return false;">Delete</a>'
                )
            );
        }

        $modalUrl = $this->generateUrl('opening_time_exception', ['siteId' => $siteId]);

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About custom opening hours</h4>
Custom opening hours are used to modify your regular weekly hours (which are defined on each site).
<br><br>
If you open at the same time each week, just create time slots when you create/edit a site.
If you have irregular opening hours, then create custom time slots.
You can mix the two, to have regular hours each week, and then create a closed time slot for a holiday.
<br><br>
Custom opening hours in the past will be deleted automatically on a regular basis.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Custom opening hours : '.$site->getName(),
                'pageTitle'  => 'Custom opening hours : '.$site->getName(),
                'addButtonText' => 'Add new',
                'entityName' => 'OpeningTimeException', // Used in the sort order handler
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
     * @Route("admin/site/{siteId}/opening-time-exception", requirements={"siteId": "\d+"}, name="opening_time_exception")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function addOpeningTimeExceptionAction(Request $request, $siteId)
    {
        $em = $this->getDoctrine()->getManager();

        $openingTimeException = new OpeningTimeException();

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');
        if (!$site = $siteRepo->find($siteId)) {
            $this->addFlash('error', "Site {$siteId} not found");
            return $this->redirectToRoute('home');
        }

        $openingTimeException->setSite($site);

        $options = [
            'action' => $this->generateUrl('opening_time_exception', ['siteId' => $siteId])
        ];

        $form = $this->createForm(OpeningTimeExceptionType::class, $openingTimeException, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $d = $form->get('date')->getData();
            $date = new \DateTime($d);
            $openingTimeException->setDate($date);

            $em->persist($openingTimeException);
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

            return $this->redirectToRoute('opening_time_exception_list', ['siteId' => $siteId]);

        }

        return $this->render(
            'modals/settings/openingTimeException.html.twig',
            array(
                'title' => 'Add custom hours for '.$site->getName(),
                'subTitle' => 'Add custom hours, or holidays',
                'form' => $form->createView(),
            )
        );
    }

}