<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController
 * @package AppBundle\Controller
 */
class SiteEditController extends Controller
{
    /**
     * @Route("site_edit_begin", name="site_edit_begin")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function siteEditBeginController(Request $request)
    {
        $this->addFlash("success", "You're now in site editing mode.
        You can add and edit pages, change theme, and edit content. <br><br>
        Note that some admin features are not available while in edit mode
        (such as creating loans) so that the site looks more similar to what your members see.");
        $this->container->get('session')->set('isEditMode', true);
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("site_edit_end", name="site_edit_end")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function siteEditEndController(Request $request)
    {
        $this->container->get('session')->set('isEditMode', false);
        return $this->redirectToRoute('home');
    }
}
