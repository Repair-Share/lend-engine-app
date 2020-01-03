<?php

namespace AppBundle\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;

class ContactGET extends AbstractFOSRestController
{
    /**
     * @Route("/api/contact/1")
     */
    public function indexAction()
    {
        $data = array("hello" => "world");
        $view = $this->view($data);
        return $this->handleView($view);
    }
}