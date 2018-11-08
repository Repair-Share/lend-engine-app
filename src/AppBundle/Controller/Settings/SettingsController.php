<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\PaymentMethod;
use AppBundle\Entity\Setting;
use AppBundle\Form\Type\SettingsReservationsType;
use AppBundle\Form\Type\SettingsTemplatesType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\SettingsType;
use Symfony\Component\HttpFoundation\Session\Session;

class SettingsController extends Controller
{

    /**
     * @Route("admin/settings", name="settings")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $tenantInformationService \AppBundle\Extensions\TenantInformation */
        $tenantInformationService = $this->get('tenant_information');

        // Pass tenant info in so we can control settings based on pay plan
        $options = [
            'em' => $em,
            'tenantInformationService' => $tenantInformationService
        ];

        $form = $this->createForm(SettingsType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        /** @var $tenantRepo \AppBundle\Repository\AccountRepository */
        $tenantRepo = $em->getRepository('AppBundle:Account');
        $accountCode = $this->get('session')->get('account_code');

        /** @var \AppBundle\Entity\Account $tenant */
        $tenant = $tenantRepo->findOneBy(['stub' => $accountCode]);

        $locale = $tenantInformationService->getLocale();

        if ($form->isSubmitted()) {

            $timeZone = '';
            foreach ($request->get('settings') AS $setup_key => $setup_data) {

                if ($this->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }
                    if (is_array($setup_data)) {
                        $setup_data = implode(',', $setup_data);
                    }
                    $setting->setSetupValue($setup_data);

                    if ($setup_key == 'org_locale' && $setup_data != $locale) {
                        $this->addFlash('success', "Language changed for all users.");
                        $this->setLanguageForAllUsers($setup_data);
                    }

                    $em->persist($setting);

                    if ($setup_key == 'org_timezone') {
                        $timeZone = $setup_data;
                    }
                }

            }

            // Update the _core DB too
            if ($timeZone) {
                $tenant->setTimeZone($timeZone);
                $em->persist($tenant);
            }

            try {
                $em->flush();
                $this->addFlash('success','Settings updated.');
            } catch (\PDOException $e) {
                $this->addFlash('error','Error updating settings.');
            }

            return $this->redirectToRoute('settings');
        }

        return $this->render('settings/settings.html.twig', array(
            'form'      => $form->createView(),
            'title'     => 'Settings',
            'php_time'  => new \DateTime()
        ));

    }

    /**
     * @Route("admin/settings/templates", name="settings_templates")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsTemplatesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $options = [
            'em' => $em
        ];
        $form = $this->createForm(SettingsTemplatesType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        if ($form->isSubmitted()) {
            foreach ($request->get('settings_templates') AS $setup_key => $setup_data) {
                if ($this->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }
                    $setting->setSetupValue($setup_data);
                    $em->persist($setting);
                }
            }
            try {
                $em->flush();
                $this->addFlash('success','Templates updated.');
            } catch (\PDOException $e) {
                $this->addFlash('error','Error updating templates.');
            }
            return $this->redirectToRoute('settings_templates');
        }

        return $this->render('settings/settings_templates.html.twig', array(
            'form' => $form->createView()
        ));

    }

    /**
     * @Route("admin/settings/reservations", name="settings_reservations")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsReservationsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $options = [
            'em' => $em
        ];
        $form = $this->createForm(SettingsReservationsType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        if ($form->isSubmitted()) {
            foreach ($request->get('settings_reservations') AS $setup_key => $setup_data) {
                if ($this->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }
                    $setting->setSetupValue($setup_data);
                    $em->persist($setting);
                }
            }
            try {
                $em->flush();
                $this->addFlash('success','Settings updated.');
            } catch (\PDOException $e) {
                $this->addFlash('error','Error updating settings.');
            }
            return $this->redirectToRoute('settings_reservations');
        }

        return $this->render('settings/settings_reservations.html.twig', array(
            'form' => $form->createView()
        ));

    }

    /**
     * @param $key
     * @return bool
     */
    private function isValidSettingsKey($key)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');
        $validKeys = $repo->getSettingsKeys();

        if (in_array($key, $validKeys)) {
            return true;
        }

        return false;
    }

    /**
     * @param $locale string
     * @return bool
     */
    private function setLanguageForAllUsers($locale)
    {
        $db = $this->get('database_connection');
        $db->executeQuery("UPDATE contact SET locale = '{$locale}'");
        return true;
    }

}