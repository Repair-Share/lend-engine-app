<?php

namespace AppBundle\Controller\Admin\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class UserListController extends Controller
{
    /**
     * @Route("admin/users/list", name="users_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction()
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        // Get users from the DB
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:Contact')->findAllStaff();

        return $this->render(
            'user/users_list.html.twig',
            array('users' => $users)
        );
    }
}