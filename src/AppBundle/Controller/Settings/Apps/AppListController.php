<?php

namespace AppBundle\Controller\Settings\Apps;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AppListController extends Controller
{
    /**
     * @Route("admin/apps/list", name="app_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {
        // admin only
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        /** @var \AppBundle\Services\Apps\AppService $appService */
        $appService = $this->get("service.apps");
        $apps = $appService->getAll();

        return $this->render(
            'settings/apps.html.twig', [
                'apps' => $apps
            ]
        );
    }

}