<?php

namespace FOS\UserBundle\Mailer;

use FOS\UserBundle\Model\UserInterface;

/**
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface MailerInterface
{
    /**
     * Send an email to a user to confirm the account creation
     *
     * @param UserInterface $user
     */
    function sendConfirmationEmailMessage(UserInterface $user);

    /**
     * Send an email to a user to confirm the password reset
     *
     * @param UserInterface $user
     */
    function sendResettingEmailMessage(UserInterface $user);

    /**
     * Send an email to a user to confirm the changed email address.
     *
     * @param UserInterface $user
     * @param string        $confirmationUrl
     * @param string        $toEmail
     */
    public function sendUpdateEmailConfirmation(UserInterface $user, $confirmationUrl, $toEmail);
}