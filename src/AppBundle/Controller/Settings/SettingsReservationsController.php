<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\SettingsReservationsType;

class SettingsReservationsController extends Controller
{
    /**
     * @Route("admin/settings/reservations", name="settings_reservations")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsReservationsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $options = [
            'em' => $em,
            'settingsService' => $settingsService
        ];
        $form = $this->createForm(SettingsReservationsType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        if ($form->isSubmitted()) {
            foreach ($request->get('settings_reservations') AS $setup_key => $setup_data) {
                if ($settingsService->isValidSettingsKey($setup_key)) {
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

}