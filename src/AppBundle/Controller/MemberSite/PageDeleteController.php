<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageDeleteController
 * @package AppBundle\Controller\MemberSite
 */
class PageDeleteController extends Controller
{

    /**
     * @Route("page/{pageId}/delete", name="public_page_delete")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function pageDeleteController($pageId)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $page = $this->getDoctrine()->getRepository('AppBundle:Page')->find($pageId);
        if (!$page) {
            $this->addFlash("error", "Page with ID {$pageId} not found.");
            return $this->redirectToRoute('home');
        }

        if (!$this->container->get('security.authorization_checker')->isGranted("ROLE_ADMIN")) {
            $this->addFlash("error", "You don't have permission to delete pages.");
            return $this->redirectToRoute('home');
        }

        $em->remove($page);
        $em->flush();
        $page->setUpdatedBy($user);

        $this->addFlash("success", "Page deleted.");
        return $this->redirectToRoute('home');
    }

}
