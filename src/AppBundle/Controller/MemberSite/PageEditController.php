<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Page;
use AppBundle\Form\Type\Settings\PageType;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController
 * @package AppBundle\Controller
 */
class PageEditController extends Controller
{
    /**
     * @Route("page/{pageId}", name="public_page_edit")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function editPageAction($pageId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $tenantInformationService = $this->container->get('tenant_information');

        if ($pageId == "new") {

            if (!$tenantInformationService->getFeature("Page")) {
                $this->addFlash("info", "Adding new pages isn't available on your plan. Please upgrade via the billing page at Admin &raquo; Settings.");
                return $this->redirectToRoute("home");
            }

            $page = new Page();
            $page->setCreatedBy($user);
            $page->setUpdatedBy($user);

        } else {

            $page = $this->getDoctrine()->getRepository('AppBundle:Page')->find($pageId);
            if (!$page) {
                $this->addFlash("error", "Page with ID {$pageId} not found.");
                return $this->redirectToRoute('home');
            }
            $page->setUpdatedBy($user);

            if (!$tenantInformationService->getFeature("Page") && $pageId > 1) {
                $this->addFlash("info", "Adding extra pages isn't available on your plan. Please upgrade via the billing page at Admin &raquo; Settings.");
                return $this->redirectToRoute("home");
            }

        }

        $form = $this->createForm(PageType::class, $page, array(
            'action' => $this->generateUrl('public_page_edit', array('pageId' => $pageId))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try{

                $em->persist($page);
                $em->flush();

                $pageId = $page->getId();
                $this->addFlash('success', 'Page saved.');

                return $this->redirectToRoute('public_page_by_slug', ['pageId' => $pageId, 'slug' => $page->getSlug()]);

            } catch (UniqueConstraintViolationException $e) {

                // Not currently enforced at the DB level
                $this->addFlash('error', 'A page with the name "'.$page->getName().'" already exists.');
                return $this->redirectToRoute('public_page_by_slug', ['pageId' => $pageId, 'slug' => $page->getSlug()]);

            } catch (PDOException $e) {

                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('public_page_by_slug', ['pageId' => $pageId, 'slug' => $page->getSlug()]);

            }

        }

        if ($page->getId()) {
            if ($page->getUrl()) {
                $title = 'Edit menu link';
            } else {
                $title = 'Edit custom page';
            }
        } else {
            $title = 'Add a custom page or menu link';
        }

        return $this->render(
            'member_site/pages/page_edit.html.twig',
            array(
                'title' => $title,
                'subTitle' => '',
                'form' => $form->createView(),
                'page' => $page
            )
        );

    }

    /**
     * @Route("page/{pageId}/delete", name="public_page_delete")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function pageDeleteController($pageId, Request $request)
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
