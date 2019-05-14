<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Setting;
use AppBundle\Form\Type\Settings\SettingsLabelsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class SettingsLabelsController extends Controller
{

    /**
     * @Route("admin/settings/labels", name="settings_labels")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsLabelsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $tenantService \AppBundle\Services\TenantService */
        $tenantService = $this->get('service.tenant');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        // Pass tenant info in so we can control settings based on pay plan
        $options = [
            'em' => $em,
            'tenantService' => $tenantService,
            'settingsService' => $settingsService
        ];

        $form = $this->createForm(SettingsLabelsType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        if ($form->isSubmitted()) {

            foreach ($request->get('settings_labels') AS $setup_key => $setup_data) {
                if ($settingsService->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }
                    if (is_array($setup_data)) {
                        $setup_data = implode(',', $setup_data);
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
            return $this->redirectToRoute('settings_labels');
        }

        return $this->render('settings/settings_labels.html.twig', array(
            'form' => $form->createView()
        ));
    }

}