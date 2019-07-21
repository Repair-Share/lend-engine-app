<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PaymentMethod;
use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class StripeController extends Controller
{

    /**
     * @Route("stripe-connect", name="stripe_connect")
     */
    public function stripeConnect(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        // XX.lend-engine-app.com unless a custom SSL domain is set
        $accountDomain = $this->get('service.tenant')->getAccountDomain();
        $accountCode   = $this->get('service.tenant')->getAccountCode();

        /** @var $settingsRepo \AppBundle\Entity\Setting */
        $settingsRepo =  $em->getRepository('AppBundle:Setting');

        $accessToken    = '';
        $refreshToken   = '';
        $publishableKey = '';
        $stripeAccount  = '';

        if ($code = $request->get('code')) {

            if (in_array($accountCode, ['dev', 'localhost:8000'])) {
                $secret   = getenv('SYMFONY__STRIPE_SECRET');
                $clientId = getenv('SYMFONY__STRIPE_CLIENT');
            } else {
                $secret   = getenv('SYMFONY__STRIPE_SECRET');
                $clientId = getenv('SYMFONY__STRIPE_CLIENT');
            }

            $token_request_body = array(
                'grant_type'    => 'authorization_code',
                'client_id'     => $clientId,
                'code'          => $code,
                'client_secret' => $secret
            );

            $token_uri = 'https://connect.stripe.com/oauth/token';

            $req = curl_init($token_uri);
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req, CURLOPT_POST, true );
            curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($token_request_body));

            // TODO: Additional error handling
            $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
            $resp = json_decode(curl_exec($req), true);
            curl_close($req);

            if (isset($resp['access_token']) && isset($resp['stripe_publishable_key'])) {
                $accessToken    = $resp['access_token'];
                $publishableKey = $resp['stripe_publishable_key'];
                $refreshToken   = $resp['refresh_token'];
                $stripeAccount  = $resp['stripe_user_id'];
            } else {
                die('No access token');
            }

        }

        if ($error = $request->get('error')) {
            die($error.':'.$request->get('error_description'));
        }

        if ($accessToken && $publishableKey) {

            // Create a new payment method if one does not exist
            /** @var $repo \AppBundle\Entity\PaymentMethod */
            $repo =  $em->getRepository('AppBundle:PaymentMethod');
            if (!$paymentMethod = $repo->findOneByName('Stripe')) {
                $paymentMethod = new PaymentMethod();
                $paymentMethod->setName("Stripe");
                $em->persist($paymentMethod);
                try {
                    $em->flush();
                } catch (\PDOException $e) {
                    $this->addFlash('error','Error creating Stripe payment method.');
                }
            }

            if (!$setting = $settingsRepo->findOneBy(['setupKey' => 'stripe_access_token'])) {
                $setting = new Setting();
                $setting->setSetupKey('stripe_access_token');
            }
            $setting->setSetupValue($accessToken);
            $em->persist($setting);

            if (!$rToken = $settingsRepo->findOneBy(['setupKey' => 'stripe_refresh_token'])) {
                $rToken = new Setting();
                $rToken->setSetupKey('stripe_refresh_token');
            }
            $rToken->setSetupValue($refreshToken);
            $em->persist($rToken);

            if (!$sAccount = $settingsRepo->findOneBy(['setupKey' => 'stripe_user_id'])) {
                $sAccount = new Setting();
                $sAccount->setSetupKey('stripe_user_id');
            }
            $sAccount->setSetupValue($stripeAccount);
            $em->persist($sAccount);

            if (!$pKey = $settingsRepo->findOneBy(['setupKey' => 'stripe_publishable_key'])) {
                $pKey = new Setting();
                $pKey->setSetupKey('stripe_publishable_key');
            }
            $pKey->setSetupValue($publishableKey);
            $em->persist($pKey);

            $paymentMethodId = $paymentMethod->getId();

            if (!$stripePaymentMethod = $settingsRepo->findOneBy(['setupKey' => 'stripe_payment_method'])) {
                $stripePaymentMethod = new Setting();
                $stripePaymentMethod->setSetupKey('stripe_payment_method');
            }
            $stripePaymentMethod->setSetupValue($paymentMethodId);
            $em->persist($stripePaymentMethod);

            try {
                $em->flush();
                $this->addFlash('success','Stripe is now connected!');

                $this->get('session')->remove('stripe_access_token');
                $this->get('session')->remove('stripe_publishable_key');

            } catch (\PDOException $e) {
                $this->addFlash('error','Error updating settings.');
            }

            if (strstr($accountDomain, 'lend-engine-app')) {
                $protocol = 'http';
            } else {
                $protocol = 'https';
            }

            return $this->redirect("{$protocol}://{$accountDomain}/admin/settings");

        } else {

            $this->addFlash('error','No stripe token found in session.');
            return $this->redirectToRoute('settings');

        }

    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("stripe-disconnect", name="stripe_disconnect")
     */
    public function stripeDisconnect()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $em->getRepository('AppBundle:Setting');

        if ($accessToken = $repo->findOneBy(['setupKey' => 'stripe_access_token'])) {
            $em->remove($accessToken);
        }
        if ($publishableKey = $repo->findOneBy(['setupKey' => 'stripe_publishable_key'])) {
            $em->remove($publishableKey);
        }
        if ($userId = $repo->findOneBy(['setupKey' => 'stripe_user_id'])) {
            $em->remove($userId);
        }
        if ($refreshToken = $repo->findOneBy(['setupKey' => 'stripe_refresh_token'])) {
            $em->remove($refreshToken);
        }
        if ($stripePaymentMethod = $repo->findOneBy(['setupKey' => 'stripe_payment_method'])) {
            $em->remove($stripePaymentMethod);
        }

        try {
            $em->flush();
            $this->addFlash('success','Stripe is now disconnected.');
        } catch (\PDOException $e) {
            $this->addFlash('error','Database error updating settings.');
        }

        return $this->redirectToRoute('settings');
    }

}