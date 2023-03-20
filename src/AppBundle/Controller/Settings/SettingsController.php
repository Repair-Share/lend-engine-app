<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\Settings\SettingsType;

class SettingsController extends Controller
{

    /**
     * @Route("admin/settings/general", name="settings")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        echo '<pre style="display: none">';

        if (true
            && getenv('APP_ENV') === 'prod'
            //&& $serverName !== 'lend-engine-staging'
            && $_SERVER['HTTP_HOST']
            && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https'
        ) {
            echo 'REDIRECTION';
        }else{
            echo 'NO Redirection';
        }

        //echo print_r($_SERVER);

        echo '</pre>';

        /** @var $tenantService \AppBundle\Services\TenantService */
        $tenantService = $this->get('service.tenant');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        // Pass tenant info in so we can control settings based on pay plan
        $options = [
            'em' => $em,
            'tenantService' => $tenantService,
            'settingsService' => $settingsService,
        ];

        $form = $this->createForm(SettingsType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $settingsService->getTenant(false);

        if ($form->isSubmitted()) {

            foreach ($request->get('settings') AS $setup_key => $setup_data) {
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

                // Also update Core (_core DB)
                $settingsService->setTenant($tenant);
                $settingsService->updateCore($tenant->getStub());

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

}