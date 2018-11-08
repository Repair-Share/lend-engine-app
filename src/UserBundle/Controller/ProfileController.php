<?php

namespace UserBundle\Controller;

use FOS\UserBundle\Controller\ProfileController as BaseProfileController;
use FOS\UserBundle\Model\UserInterface;

class ProfileController extends BaseProfileController
{
    protected function setFlash($action, $value)
    {
        $action = str_replace("fos_user_", "", $action); //Remove "fos_user_" prefix
        parent::setFlash($action, $value);
    }
}