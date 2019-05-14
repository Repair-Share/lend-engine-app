<?php

/**
 * A class created so that FOSUserBundle sends emails via PostMark
 *
 */
namespace AppBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class FOSMailer implements MailerInterface
{

    protected $router;
    protected $container;

    public function __construct(Container $container, \Twig_Environment $twig, RouterInterface $router)
    {
        $this->container = $container;
        $this->twig = $twig;
        $this->router = $router;
    }

    /**
     * @param UserInterface $user
     */
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        $template        = $this->container->getParameter('fos_user.registration.confirmation.template');
        $fromCompanyName = $this->container->get('service.tenant')->getCompanyName();
        $replyToEmail    = $this->container->get('service.tenant')->getCompanyEmail();

        $url = $this->router->generate('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

        $message = $this->twig->render(
            $template,
            array(
                'user' => $user,
                'confirmationUrl' => $url
            )
        );

        $toEmail = $user->getEmail();

        $client = new PostmarkClient($this->container->getParameter('postmark_api_key'));
        $client->sendEmail(
            "{$fromCompanyName} <hello@lend-engine.com>",
            $toEmail,
            "Confirm your registration.",
            $message,
            null,
            null,
            null,
            $replyToEmail
        );
    }

    /**
     * @param UserInterface $user
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {
        $template        = $this->container->getParameter('fos_user.resetting.email.template');
        $fromCompanyName = $this->container->get('service.tenant')->getCompanyName();
        $replyToEmail    = $this->container->get('service.tenant')->getCompanyEmail();

        $url = $this->router->generate('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

        $message = $this->twig->render(
            $template,
            array(
                'user' => $user,
                'confirmationUrl' => $url
            )
        );

        $toEmail = $user->getEmail();

        $client = new PostmarkClient($this->container->getParameter('postmark_api_key'));
        $client->sendEmail(
            "{$fromCompanyName} <hello@lend-engine.com>",
            $toEmail,
            "Reset your password.",
            $message,
            null,
            null,
            null,
            $replyToEmail
        );

    }
}