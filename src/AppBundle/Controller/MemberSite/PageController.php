<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController
 * @package AppBundle\Controller
 */
class PageController extends Controller
{
    /**
     * @Route("p/{pageId}", requirements={"pageId": "\d+"}, name="public_page")
     */
    public function showPageAction($pageId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\PageRepository $repo */
        $repo = $em->getRepository('AppBundle:Page');

        /** @var $page \AppBundle\Entity\Page */
        if (!$page = $repo->find($pageId)) {
            $this->addFlash("error", "Page with ID {$pageId} not found.");
            return $this->redirectToRoute('home');
        }

        // Permission check
        if ($page->getVisibility() == "ADMIN") {
            if (!$this->container->get('security.authorization_checker')->isGranted("ROLE_ADMIN")) {
                $this->addFlash("error", "Sorry, you can't access that.");
                return $this->redirectToRoute('home');
            }
        } else if ($page->getVisibility() == "MEMBER") {
            if (!$this->container->get('security.authorization_checker')->isGranted("ROLE_USER")) {
                $this->addFlash("error", "Sorry, you can't access that.");
                return $this->redirectToRoute('home');
            }
        } else if ($page->getVisibility() == "HIDDEN" && !$this->container->get('tenant_information')->getIsEditMode()) {
            $this->addFlash("error", "Sorry, you can't access that.");
            return $this->redirectToRoute('home');
        }

        return $this->render('member_site/pages/page.html.twig', [
            'page' => $page,
            'pageTitle' => $page->getTitle()
        ]);
    }

}
