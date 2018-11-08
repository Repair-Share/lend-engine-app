<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class LocaleController extends Controller
{

    /**
     * @Route("locale", name="set_locale")
     */
    public function chooseContactAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        if ($locale = $request->get('loc')) {

            if (!$user = $this->getUser()) {
                $session->set('_locale', $locale);
                return $this->redirectToRoute('home');
            }

            $user->setLocale($locale);
            $em->persist($user);

            try {
                $em->flush();
                $session->set('_locale', $user->getLocale());
            } catch (\Exception $e) {

            }
        }

        return $this->redirectToRoute('home');
    }

}