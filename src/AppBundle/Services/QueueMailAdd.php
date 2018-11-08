<?php

/**
 *
 * NOT YET USED
 * Preparing for when emails are sent with RabbitMQ
 *
 */
namespace AppBundle\Services;

class QueueMailAdd
{

    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function publish($message = 'no message')
    {


    }

}