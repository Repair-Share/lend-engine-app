<?php

namespace AppBundle\Controller\Settings\Apps;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AppInstallController extends Controller
{
    /**
     * @Route("admin/apps/{code}/install", name="app_install")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function installApp($code)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        /** @var \AppBundle\Services\Apps\AppService $appService */
        $appService = $this->get("service.apps");
        $appService->install($code);

        return $this->redirectToRoute('app_list');
    }

    /**
     * @Route("admin/apps/{code}/deactivate", name="app_deactivate")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function deactivateApp($code)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        /** @var \AppBundle\Services\Apps\AppService $appService */
        $appService = $this->get("service.apps");
        $appService->deactivate($code);

        return $this->redirectToRoute('app_list');
    }

}