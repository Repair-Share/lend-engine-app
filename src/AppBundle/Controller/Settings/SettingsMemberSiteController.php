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
            'em'              => $em,
            'tenantService'   => $tenantService,
            'settingsService' => $settingsService
        ];

        $form = $this->createForm(SettingsMemberSiteType::class, null, $options);

        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo = $em->getRepository('AppBundle:Setting');

        $locale = $tenantService->getLocale();

        // Cancel the custom domain request
        if (isset($_REQUEST['customDomainCancel'])) {

            $subject = 'Custom domain CANCEL request for ' . $tenantService->getCompanyName();

            $reqs = [
                'site_domain'           => $settingsService->getSettingValue('site_domain'),
                'site_domain_provider'  => $settingsService->getSettingValue('site_domain_provider'),
                'site_domain_req_name'  => $settingsService->getSettingValue('site_domain_req_name'),
                'site_domain_req_email' => $settingsService->getSettingValue('site_domain_req_email'),
            ];

            $domainReqName = $reqs['site_domain_req_name'];

            $message = '
                
                    Dear Support,
                    
                    ' . $domainReqName . ' is requested to CANCEL a custom domain to ' . $tenantService->getCompanyName() . '.
                    
                    ' . $this->emailRequestDetails($reqs, $tenantService);

            $message = nl2br($message);

            $emailService->send(
                'support@lend-engine.com',
                'Lend Engine Support',
                $subject,
                $message
            );

            $this->clearSiteDomainDetails();

            $this->addFlash('success', 'Your custom domain request has been cancelled.');

            return $this->redirectToRoute('settings_member_site');

        } elseif ( // Check if the custom domain is already set up
            !$form->isSubmitted()
            && !isset($_REQUEST['customDomainAdded']) // Avoid infinity loop if data was not saved
        ) {

            $currentDomain   = $tenantService->getAccountDomain(false);
            $requestedDomain = $settingsService->getSettingValue('site_domain');

            if ($requestedDomain) {
                $requestedDomain = str_ireplace(['http://', 'https://', 'www.'], '', $requestedDomain);
            }

            if ($currentDomain && $requestedDomain && $currentDomain === $requestedDomain) {

                $this->clearSiteDomainDetails();

                $this->addFlash('success', 'Your custom domain has been set up.');

                return $this->redirectToRoute('settings_member_site', ['customDomainAdded' => true]);

            }

        }

        if ($form->isSubmitted()) {

            $reqs = $request->get('settings_member_site');

            if (array_key_exists('site_domain', $reqs)) {

                $domainReqName  = $reqs['site_domain_req_name'];

                $reqs['site_domain_req_time'] = date('c');

                $subject = 'Custom domain request for ' . $tenantService->getCompanyName();

                $message = '
                
                    Dear Support,
                    
                    ' . $domainReqName . ' is requested to SET UP a custom domain to ' . $tenantService->getCompanyName() . '.
                    
                    ' . $this->emailRequestDetails($reqs, $tenantService);

                $message = nl2br($message);

                $emailService->send(
                    'support@lend-engine.com',
                    'Lend Engine Support',
                    $subject,
                    $message
                );

            }

            foreach ($reqs as $setup_key => $setup_data) {
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
                $this->addFlash('success', 'Settings updated.');
            } catch (\PDOException $e) {
                $this->addFlash('error', 'Error updating settings.');
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
        $data   = ['hostname' => $domain];
        try {
            $heroku->post('apps/lend-engine-eu-plus/domains/', $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function clearSiteDomainDetails()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo = $em->getRepository('AppBundle:Setting');

        foreach (
            [
                'site_domain',
                'site_domain_provider',
                'site_domain_req_name',
                'site_domain_req_email',
                'site_domain_req_time'
            ] as $setup_key
        ) {

            if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                $setting = new Setting();
                $setting->setSetupKey($setup_key);
            }

            $setting->setSetupValue('');
            $em->persist($setting);

        }

        $em->flush();
    }

    private function emailRequestDetails(array $reqs, $tenantService)
    {
        return '
            <strong>Request Details</strong>
            Custom Domain: <strong>' . $reqs['site_domain'] . '</strong>
            Domain Provider: ' . $reqs['site_domain_provider'] . '
            Name: ' . $reqs['site_domain_req_name'] . '
            E-mail: ' . $reqs['site_domain_req_email'] . '
            
            <strong>The client details:</strong>
            Company Name: ' . $tenantService->getCompanyName() . '
            Owner Name: ' . $tenantService->getAccountOwnerName() . '
            Owner Email: ' . $tenantService->getAccountOwnerEmail() . '
            Account Domain: ' . $tenantService->getAccountDomain() . '
        ';
    }

}