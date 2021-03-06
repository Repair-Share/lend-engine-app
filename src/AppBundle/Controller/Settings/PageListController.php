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

class PageListController extends Controller
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
            'Title / link',
            'Slug / URL',
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
                    $i->getSlug(),
                    $i->getVisibility(),
                    ''
                )
            );

        }

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About site pages and menu links</h4>
<br>
<div class="row">
<div class="col-md-5">
    <img src="/images/screenshots/site_menu_edit.png" class="img-responsive img-rounded">
</div>
<div class="col-md-7">
You can add extra content to your member site using 'pages' or 'links'.
Both appear as menu items in the main menu on the left hand side of the screen, above any categories you have created.<br><br>
Pages are good for content like Terms and Conditions or a Privacy Policy, or information about your library.
Links are good for taking users to other websites, or to a certain filter of items within your Lend Engine member site.
Links out to other sites (they will contain "http") open in a new tab. Links within your member site (starting with "/") will open in the same tab.
<br><br>
You can create pages that are visible for everyone, for members only, or for staff only (useful for library operating guides).
Keep a page hidden until you are ready to set it visible.<br><br>
Use the 'slug' to define your own page URLs for better search engine ranking.
</div>
</div>
<br>
<h4>Pages are created and managed using the <strong>site edit mode</strong>.<br>
Click the "edit website" button in the admin toolbox on your member site to begin site editing.</h4>

EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Site pages and links',
                'entityName' => 'Page', // used for AJAX delete handler
                'pageTitle'  => 'Site pages and links',
                'addButtonText' => '',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => '',
                'noActions' => true,
                'help' => $helpText
            )
        );
    }

}