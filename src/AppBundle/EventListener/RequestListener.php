<?php

namespace AppBundle\EventListener;

class RequestListener
{
    public function onKernelResponse($event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $response = $event->getResponse();

        /*
         * The page can only be displayed in a frame on the same origin as the page itself.
         * It helps the ClickJacking Protection
         */
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        /*
         * The Access-Control-Allow-Origin response header indicates whether the response can be shared with
         * requesting code from the given origin.
         */
        $response->headers->set('Access-Control-Allow-Origin', '<origin>');

        /*
         * The new Content-Security-Policy HTTP response header helps you reduce XSS risks on modern browsers by declaring,
         * which dynamic resources are allowed to load.
         * https://content-security-policy.com/
         *
         * default-src: The default policy for fetching resources such as JavaScript, Images, CSS, Fonts, AJAX requests,
         * Frames, HTML5 Media
         * script-src: Defines valid sources of JavaScript.
         */
        $allowedContents = [
            '*.google-analytics.com',
            '*.googletagmanager.com',
            '*.googleapis.com',
            '*.google.com',
            '*.gstatic.com',
            'js.stripe.com',
            'cdnjs.cloudflare.com',
            '*.cloudfront.net',
            '*.bootstrapcdn.com',
            '*.fontawesome.com',
            'code.ionicframework.com',
            '*.rollbar.com'
        ];

        $response->headers->set(
            'Content-Security-Policy',
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " . implode(' ', $allowedContents)
        );
    }
}