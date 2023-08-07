<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LoginController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("access", name="access")
     */
    public function loginAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        if (!$token = $request->get('t')) {
            $this->addFlash("info", "Please log in.");
            return $this->redirectToRoute('fos_user_security_login');
        }

        if (!$email = $request->get('e')) {
            $this->addFlash("info", "Please log in first.");
            return $this->redirectToRoute('fos_user_security_login');
        }

        if (!$user = $contactRepo->findOneBy(['username' => $email])) {
            $this->addFlash("info", $email);
            $this->addFlash("info", "Please log in to see that page.");
            return $this->redirectToRoute('fos_user_security_login');
        }

        if ($user->getSecureAccessToken() != $token) {
            $this->addFlash("error", "The link in that email has expired. Please log in.");
            return $this->redirectToRoute('fos_user_security_login');
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));

        // Fire the login event manually
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        // Remove the token once it is used
        $user->setSecureAccessToken(null);
        $em->persist($user);
        $em->flush();

        if ($r = $request->get('r')) {
            return $this->redirect($r);
        }

        return $this->redirectToRoute('add_credit');
    }
}