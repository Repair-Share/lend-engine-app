<?php

/**
 * A class created so that FOSUserBundle sends emails via PostMark
 *
 */
namespace AppBundle\Mailer;

use AppBundle\Services\EmailService;
use AppBundle\Services\SettingsService;
use AppBundle\Services\TenantService;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Mailer\MailerInterface;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;

class FOSMailer implements MailerInterface
{

    protected $emailer;
    protected $router;
    protected $tenant;
    protected $settings;

    public function __construct(EmailService $emailService,
                                TenantService $tenantService,
                                \Twig_Environment $twig,
                                RouterInterface $router,
                                SettingsService $settings)
    {
        $this->emailer = $emailService;
        $this->tenant = $tenantService;
        $this->twig = $twig;
        $this->router = $router;
        $this->settings = $settings;
    }

    /**
     * @param UserInterface $user
     */
    public function sendConfirmationEmailMessage(UserInterface $user)
    {
        // If we are requiring email confirmation, there will be a token
        // Set in RegistrationSuccessListener
        if ($user->getConfirmationToken()) {
            $template = 'emails/registration.email.twig';
            $url = $this->router->generate('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);
            $subject = 'Please confirm your email address';
        } else {
            $template = 'emails/site_welcome.html.twig';
            $url = '';
            if (!$subject = $this->settings->getSettingValue('email_welcome_subject')) {
                $subject = 'Welcome to our lending library';
            }
        }

        $message = $this->twig->render(
            $template,
            [
                'user' => $user,
                'confirmationUrl' => $url,
                'email' => '',
                'password' => ''
            ]
        );

        $toEmail = $user->getEmail();
        $toName  = $user->getName();

        // Send the email
        $this->emailer->send($toEmail, $toName, $subject, $message, false);
    }

    /**
     * @param UserInterface $user
     */
    public function sendResettingEmailMessage(UserInterface $user)
    {
        $template = 'emails/fos_password_reset.email.twig';
        $url = $this->router->generate('fos_user_resetting_reset', array('token' => $user->getConfirmationToken()), UrlGeneratorInterface::ABSOLUTE_URL);

        $message = $this->twig->render(
            $template,
            [
                'user' => $user,
                'confirmationUrl' => $url
            ]
        );

        $toEmail = $user->getEmail();
        $toName  = $user->getName();

        // Send the email
        $this->emailer->send($toEmail, $toName, "Reset your password.", $message, false);
    }
}