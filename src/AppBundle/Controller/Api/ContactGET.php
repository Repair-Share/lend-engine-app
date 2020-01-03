<?php

namespace AppBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

class ContactGET extends Controller
{

    /**
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("admin/contact/{id}", name="contact", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function contactAction(Request $request, $id = 0)
    {
        $em = $this->getDoctrine()->getManager();

        return false;
    }

}