<?php

/**
 *
 * Set the UI timezone, assuming all dates are in DB as UTC
 *
 */
namespace AppBundle\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TwigDateRequestListener
{
    /** @var \Twig_Environment  */
    protected $twig;

    /** @var Session  */
    private $session;

    function __construct(\Twig_Environment $twig, Session $session) {
        $this->twig = $twig;
        $this->session = $session;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        if (!$timezone = $this->session->get('time_zone')) {
            $timezone = 'Europe/London';
        }
        $this->twig->getExtension('Twig_Extension_Core')->setTimezone($timezone);
    }
}