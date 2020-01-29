<?php

namespace AppBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ChooseSiteController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("admin/choose-site", name="choose_site")
     */
    public function chooseSiteAction(Request $request)
    {

        if ($siteId = $request->get('id')) {

            $em = $this->getDoctrine()->getManager();
            $site = $this->getDoctrine()->getRepository('AppBundle:Site')->find($siteId);

            // Save the site to the user
            $user = $this->getUser();
            $user->setActiveSite($site);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Site updated");

            $referrer = $request->headers->get('referer');
            return new RedirectResponse($referrer);
        }

        $sites = $this->getDoctrine()->getRepository('AppBundle:Site')->findBy(['isActive' => true]);

        return $this->render(
            'modals/choose_site.html.twig',
            array(
                'sites' => $sites
            )
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("admin/clear-site", name="clear_site")
     */
    public function clearSiteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Save the site to the user
        $user = $this->getUser();
        $user->setActiveSite(null);
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('fos_user_security_logout');
    }
}