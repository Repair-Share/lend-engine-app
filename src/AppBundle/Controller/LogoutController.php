<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;

class LogoutController extends Controller
{
    /**
     * required for null options in menu
     * @Route("admin/", name="null")
     */
    public function nullAction()
    {
        return $this->render('default/dashboard.html.twig', []);
    }

    /**
     * @Route("logout", name="logout", requirements = {"_locale" = "fr|en|nl"})
     */
    public function logoutAction()
    {
        return $this->redirect($this->generateUrl('homepage'));
    }

}
