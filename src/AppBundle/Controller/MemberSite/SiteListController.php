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
        $em = $this->getDoctrine()->getManager();

        /** @var $waitingListRepo \AppBundle\Repository\SiteRepository */
        $siteRepo = $this->getDoctrine()->getRepository('AppBundle:Site');
        $sites = $siteRepo->findAll();

        foreach ($sites as $site) {

            // Time to look up the address
            if ((!$site->getLng() || !$site->getLat()) && !$site->getGeocodedString()) {

                $site->geoCodeAddress();

                $em->persist($site);
                $em->flush();

            }

            /** @var $site \AppBundle\Entity\Site */
            $site->setAddress(preg_replace('/\s+/', ' ', $site->getAddress()));

        }

        echo getenv('GOOGLE_MAPS_API_KEY_JS');

        return $this->render('member_site/pages/sites.html.twig', [
            'sites'  => $sites,
            'apiKey' => base64_encode(getenv('GOOGLE_MAPS_API_KEY_JS'))
        ]);
    }

}
