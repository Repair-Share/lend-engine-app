<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Setting;
use AppBundle\Form\Type\Settings\SettingsMemberSiteType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use HerokuClient\Client as HerokuClient;

class SettingsMemberSiteController extends Controller
{

    /**
     * @Route("admin/settings/member_site", name="settings_member_site")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsMemberSiteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$apiKey = getenv('H_API_KEY')) {
            $this->addFlash('debug', "We can't connect to the custom domain provider.");
            $apiKey = 'none';
        }

        $heroku = new HerokuClient([
            'apiKey' => $apiKey
        ]);

        /** @var $tenantService \AppBundle\Services\TenantService */
        $tenantService = $this->get('service.tenant');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $herokuResult = null;
        $domainOk = false;
        $domainParts = [];

        if ($requestedDomain = $settingsService->getSettingValue('site_domain')) {
            $domainParts = explode('.', $requestedDomain);
            try {
                $herokuResult = $heroku->get('apps/lend-engine-eu-plus/domains/'.$requestedDomain);
                if ($herokuResult->hostname == $requestedDomain && $herokuResult->acm_status == 'cert issued') {
                    $domainOk = true;
                } else {
                    $this->addFlash('debug', 'Domain status : '.$herokuResult->acm_status);
                }
            } catch (\Exception $e) {
                if (strstr($e->getMessage(), 'HTTP code 404')) {
                    $this->addFlash('success', "Setting up");
                    $this->createDomain($requestedDomain);
                } else {
                    $this->addFlash('debug', $e->getMessage());
                }
            }
        }

        if ($request->get('customDomain') == 'activate') {
            $tenant = $tenantService->getTenant();
            $tenant->setDomain($requestedDomain);
            $tenant->setServer('lend-engine-eu-plus');
            $em->persist($tenant);
            $em->flush();
            $this->addFlash("success", "Your domain is now activated");
            return $this->redirectToRoute('settings_member_site');
        } else if ($request->get('customDomain') == 'deactivate') {
            $tenant = $tenantService->getTenant();
            $tenant->setDomain(null);
            $tenant->setServer('lend-engine-eu');
            $em->persist($tenant);
            $em->flush();
            $this->addFlash("success", "Your domain is now de-activated");
            return $this->redirectToRoute('settings_member_site');
        }

        // Pass tenant info in so we can control settings based on pay plan
        $options = [
            'em' => $em,
            'tenantService' => $tenantService,
            'settingsService' => $settingsService
        ];

        $form = $this->createForm(SettingsMemberSiteType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        $locale = $tenantService->getLocale();

        if ($form->isSubmitted()) {

            foreach ($request->get('settings_member_site') AS $setup_key => $setup_data) {
                if ($settingsService->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }

                    if (is_array($setup_data)) {
                        $setup_data = implode(',', $setup_data);
                    }

                    if ($setup_key == 'org_locale' && $setup_data != $locale) {
                        $this->addFlash('success', "Language changed for all users.");
                        $this->setLanguageForAllUsers($setup_data);
                    }

                    if ($setup_key == 'site_domain') {
                        $setup_data = $this->cleanDomain($setup_data);
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
            return $this->redirectToRoute('settings_member_site');
        }

        return $this->render('settings/settings_member_site.html.twig', [
            'form' => $form->createView(),
            'domainStatus' => $herokuResult,
            'domainParts' => $domainParts,
            'domainOk' => $domainOk
        ]);
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

    /**
     * @param $uri
     * @return mixed|null
     */
    private function cleanDomain($uri)
    {
        $uri = strtolower($uri);
        if ($domain = parse_url($uri)) {
            if (isset($domain['host'])) {
                return $domain['host'];
            } else {
                return $uri;
            }
        } else {
            return null;
        }
    }

    /**
     * @param $domain
     * @return bool
     */
    private function createDomain($domain)
    {
        $heroku = new HerokuClient([
            'apiKey' => getenv('H_API_KEY')
        ]);
        $data = ['hostname' => $domain];
        try {
            $heroku->post('apps/lend-engine-eu-plus/domains/', $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}