<?php

/**
 * Used to redirect the user to a different page when they register
 * A setting value determines whether users are required to confirm email address before registration is complete
 */

namespace AppBundle\EventListener;

use AppBundle\Mailer\FOSMailer;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationSuccessListener implements EventSubscriberInterface
{
    private $router;
    private $mailer;
    private $tokenGenerator;

    public function __construct(UrlGeneratorInterface $router, FOSMailer $mailer, TokenGeneratorInterface $tokenGenerator)
    {
        $this->router = $router;
        $this->mailer = $mailer;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess'
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onRegistrationSuccess(FormEvent $event)
    {
        /** @var $user \FOS\UserBundle\Model\UserInterface */
        $user = $event->getForm()->getData();

        if (0) {
            $url = $this->router->generate('registration_welcome');
            $user->setEnabled(true);
        } else {
            $url = $this->router->generate('check_email');
            $user->setEnabled(false);
            if (null === $user->getConfirmationToken()) {
                $user->setConfirmationToken($this->tokenGenerator->generateToken());
            }
        }

        $this->mailer->sendConfirmationEmailMessage($user);

        $event->setResponse(new RedirectResponse($url));
    }
}