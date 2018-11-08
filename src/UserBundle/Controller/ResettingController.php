<?php

namespace UserBundle\Controller;

use FOS\UserBundle\Controller\ResettingController as BaseResettingController;
use FOS\UserBundle\Model\UserInterface;

class ResettingController extends BaseResettingController
{

    protected function getRedirectionUrl(UserInterface $user)
    {
        return $this->container->get('router')->generate('homepage');
    }

    protected function setFlash($action, $value)
    {
        $action = str_replace("fos_user_", "", $action); //Remove "fos_user_" prefix
        parent::setFlash($action, $value);
    }

}