<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Services\Schedule\DBMigrations;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SiteController
 * @package AppBundle\Controller
 */
class MemberSiteController extends Controller
{

    /**
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

        // Check that the db schema is required to update/deploy
        // Note: $tenantService->getSchemaVersion() uses cached version, so we use
        //       $tenantService->getTenant()->getSchemaVersion() to refresh the cache
        /*if (!isset($_GET['auto_updated']) && $tenantService->getTenant()->getSchemaVersion() !== DBMigrations::LATEST_MIGRATION_VERSION) {
            return $this->redirect($this->generateUrl('auto_update'));
        }*/

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        if ($user = $this->getUser()) {
            $sessionUserId = $this->get('session')->get('sessionUserId');
            if ($sessionUserId && $user->getId() != $sessionUserId) {
                // Get the member
                $user = $contactRepo->find($sessionUserId);
            }
        }

        return $this->render('member_site/pages/home.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("help/waiting-list", name="help-waiting-list")
     */
    public function helpWaitingListAction(Request $request)
    {
        return $this->render('member_site/pages/waiting_list.html.twig', []);
    }

    /**
     * For test development of a new look and feel
     * @Route("template/", name="template")
     */
    public function testAction(Request $request)
    {
        return $this->render('member_site/template.html.twig', []);
    }

}
