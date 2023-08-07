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

        /** @var $tenantService \AppBundle\Services\TenantService */
        $tenantService = $this->get('service.tenant');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

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

            $reqs = $request->get('settings_member_site');

            if (array_key_exists('site_domain', $reqs)) {

                $domain         = $reqs['site_domain'];
                $domainProvider = $reqs['site_domain_provider'];
                $domainReqName  = $reqs['site_domain_req_name'];
                $domainReqEmail = $reqs['site_domain_req_email'];

                $subject = 'Custom domain request for ' . $tenantService->getCompanyName();

                $message = '
                
                    Dear Support,
                    
                    ' . $domainReqName . ' is requested to set up a custom domain to ' . $tenantService->getCompanyName() . '.
                    
                    <strong>Request Details</strong>
                    Custom Domain: <strong>' . $domain . '</strong>
                    Domain Provider: ' . $domainProvider . '
                    Name: ' . $domainReqName . '
                    E-mail: ' . $domainReqEmail . '
                    
                    <strong>The client details:</strong>
                    Company Name: ' . $tenantService->getCompanyName() . '
                    Owner Name: ' . $tenantService->getAccountOwnerName() . '
                    Owner Email: ' . $tenantService->getAccountOwnerEmail() . '
                    Account Domain: ' . $tenantService->getAccountDomain() . '     
                ';

                $message = nl2br($message);

                $emailService->send(
                    'andordev@gmail.com',
                    'Lend Engine Support',
                    $subject,
                    $message
                );

            }

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
            'form' => $form->createView()
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