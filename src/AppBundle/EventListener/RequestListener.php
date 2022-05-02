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
    }
}