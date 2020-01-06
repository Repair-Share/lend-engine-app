<?php

namespace AppBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiDocController extends Controller
{
    /**
     * @Route("admin/api-docs", name="api_docs")
     */
    public function apiDocs()
    {
        $openapi = \OpenApi\scan($this->get('kernel')->getProjectDir().'/src');
        return $this->render('default/api_docs.html.twig', [
            'docs' => $openapi->toYaml()
        ]);
    }
}