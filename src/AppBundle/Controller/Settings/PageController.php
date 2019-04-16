<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Page;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use AppBundle\Entity\InventoryLocation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\Settings\PageType;

class PageController extends Controller
{

    /**
     * Handles the list and the form to add (which hides in the modal)
     * @Route("admin/page/list", name="page_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        // admin only
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();
        $locations = $em->getRepository('AppBundle:Page')->findOrderedBySort($request->get('type'));

        $tableHeader = array(
            'Name',
            'Title / URL',
            'Visibility',
            '',
        );

        foreach ($locations AS $i) {

            /** @var $i \AppBundle\Entity\Page */
            $vis = $i->getVisibility();

            $tableRows[] = array(
                'id' => $i->getId(),
                'class' => $vis == "HIDDEN" ? 'item-inactive' : 'item-active',
                'data' => array(
                    $i->getName(),
                    $i->getTitle() ? $i->getTitle() : '<a href="'.$i->getUrl().'" target="_blank">'.$i->getUrl().'</a>',
                    $i->getVisibility(),
                    'Delete-'.$i->getId()
                )
            );

        }

        $modalUrl = $this->generateUrl('page');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About custom site pages and menu links</h4>
You can add extra content to your member site using 'pages' or 'links'.
Both appear as menu items in the main menu on the left hand side of the screen, above any categories you have created.<br><br>
Pages are good for content like Terms and Conditions or a Privacy Policy, or information about your library.
Links are good for taking users to other websites, or to a certain filter of items within your Lend Engine member site.
Links out to other sites (they will contain "http") open in a new tab. Links within your member site (starting with "/") will open in the same tab.
<br><br>
You can create pages that are visible for everyone, for members only, or for staff only (useful for library operating guides).
Keep a page hidden until you are ready to set it visible. Menu items appear in alphabetical order.<br><br>
<span class="label bg-orange">NEW</span> - change the order of your pages in the menu by dragging items using the icon on the left of each row.

EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Custom pages and links',
                'entityName' => 'Page', // used for AJAX delete handler
                'pageTitle'  => 'Custom pages and links',
                'addButtonText' => 'Add a page or link',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing pages
     * @Route("admin/page/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="page")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function pageAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if ($id) {
            $page = $this->getDoctrine()
                ->getRepository('AppBundle:Page')
                ->find($id);
            if (!$page) {
                throw $this->createNotFoundException(
                    'No page found for id '.$id
                );
            }
            $page->setUpdatedBy($user);
        } else {
            $page = new Page();
            $page->setCreatedBy($user);
            $page->setUpdatedBy($user);
        }

        $form = $this->createForm(PageType::class, $page, array(
            'action' => $this->generateUrl('page', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try{

                $em->persist($page);
                $em->flush();

                $this->addFlash('success', 'Page saved.');

                return $this->redirectToRoute('page_list');

            } catch (UniqueConstraintViolationException $e) {

                // Not currently enforced at the DB level
                $this->addFlash('error', 'A page with the name "'.$page->getName().'" already exists.');
                return $this->redirectToRoute('page_list');

            } catch (PDOException $e) {

                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('page_list');

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
            'modals/settings/page.html.twig',
            array(
                'title' => $title,
                'subTitle' => '',
                'form' => $form->createView(),
                'page' => $page
            )
        );

    }

}