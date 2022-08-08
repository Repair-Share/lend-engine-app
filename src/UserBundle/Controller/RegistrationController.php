<?php

// src/UserBundle/Controller/RegistrationController.php
namespace UserBundle\Controller;

use FOS\UserBundle\Controller\RegistrationController as BaseController;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RegistrationController extends BaseController
{
    private $eventDispatcher;
    private $formFactory;
    private $userManager;
    private $tokenStorage;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        FactoryInterface $formFactory,
        UserManagerInterface $userManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory     = $formFactory;
        $this->userManager     = $userManager;
        $this->tokenStorage    = $tokenStorage;
    }

    /**
     * @param  Request  $request
     *
     * @return Response
     */
    public function registerAction(Request $request)
    {
        $error = '';

        /** @var \AppBundle\Services\Apps\RecaptchaService $recaptcha */
        $recaptcha = $this->get('service.recaptcha');

        $recaptchaActive = $recaptcha->installedConfiguredAndActive();

        $user = $this->userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $this->formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {

                $recaptchaOk = true;

                if ($recaptchaActive) {

                    $token = $request->get('token');

                    $recaptchaOk = $recaptcha->check($token);

                }

                if (!$recaptchaOk) {
                    $error = 'msg_fail.recaptcha_error';
                } else {

                    $event = new FormEvent($form, $request);
                    $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                    $this->userManager->updateUser($user);

                    if (null === $response = $event->getResponse()) {

                        $url = $this->generateUrl('fos_user_registration_confirmed');

                        $response = new RedirectResponse($url);
                    }

                    $this->eventDispatcher->dispatch(
                        FOSUserEvents::REGISTRATION_COMPLETED,
                        new FilterUserResponseEvent($user, $request, $response)
                    );

                    return $response;

                }

            }

            $event = new FormEvent($form, $request);
            $this->eventDispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        return $this->render('@FOSUser/Registration/register.html.twig', array(
            'form'            => $form->createView(),
            'recaptchaActive' => $recaptchaActive,
            'siteKey'         => $recaptcha->getSiteKey(),
            'error'           => $error
        ));
    }
}
