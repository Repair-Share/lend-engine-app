<?php

/**
 * Show a list of sites for a page with a map on it
 */

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SiteListController
 * @package AppBundle\Controller
 */
class SiteListController extends Controller
{
    /**
     * @Route("sites", name="public_site_list")
     */
    public function publicSiteListAction(Request $request)
    {
        /** @var $waitingListRepo \AppBundle\Repository\SiteRepository */
        $siteRepo = $this->getDoctrine()->getRepository('AppBundle:Site');
        $sites = $siteRepo->findAll();

        foreach ($sites AS $site) {
            /** @var $site \AppBundle\Entity\Site */
            $site->setAddress(preg_replace('/\s+/', ' ', $site->getAddress()));
        }

        return $this->render('public/pages/sites.html.twig', [
            'sites' => $sites
        ]);
    }

}
