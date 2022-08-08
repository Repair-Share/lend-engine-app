<?php

namespace AppBundle\Services\Apps;

class RecaptchaService
{
    /** @var \AppBundle\Services\Apps\AppService */
    private $appService;

    private $siteKey;
    private $secretKey;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->app        = $this->appService->get('recaptcha');

        if (isset($this->app['settings']['site_key']['data'])) {
            $this->siteKey = $this->app['settings']['site_key']['data'];
        }

        if (isset($this->app['settings']['secret_key']['data'])) {
            $this->secretKey = $this->app['settings']['secret_key']['data'];
        }
    }

    /**
     * @return mixed
     */
    public function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @return bool
     */
    public function installedConfiguredAndActive()
    {
        if (!$this->app) {
            return false;
        }

        if ($this->app['status'] !== 'active') {
            return false;
        }

        if (!$this->siteKey || !$this->secretKey) {
            return false;
        }

        return true;
    }

    public function check($token)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                [
                    'secret' => $this->getSecretKey(),
                    'response' => $token
                ]
            )
        );

        $response = curl_exec($ch);

        curl_close($ch);

        $responseArr = json_decode($response, true);

        if ($responseArr['success'] == 1 && $responseArr['score'] > 0.5) {
            return true;
        }

        return false;
    }

}