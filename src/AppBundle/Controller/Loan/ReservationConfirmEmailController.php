<?php

namespace AppBundle\Controller\Loan;

use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ReservationConfirmEmailController extends Controller
{

    /**
     * @Route("admin/{loanId}/confirm-email/", name="reservation_confirm_email", defaults={"loanId" = 0}, requirements={"loanId": "\d+"})
     * @param $loanId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function reservationConfirmEmail($loanId)
    {
        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $this->getDoctrine()->getRepository('AppBundle:Loan')->find($loanId)) {
            $this->addFlash('error', "Could not find loan ID {$loanId}");
            return $this->redirectToRoute('loan_list');
        }

        $senderName = $this->get('service.tenant')->getCompanyName();
        $senderEmail = $this->get('service.tenant')->getCompanyEmail();
        $locale = $loan->getContact()->getLocale();

        // Send email confirmation
        if ($toEmail = $loan->getContact()->getEmail()) {

            if (!$subject = $this->get('settings')->getSettingValue('email_reserve_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.reservation_confirm.subject', [], 'emails', $locale);
            }

            try {
                $client = new PostmarkClient($this->getParameter('postmark_api_key'));

                // Save and switch locale for sending the email
                $sessionLocale = $this->get('translator')->getLocale();
                $this->get('translator')->setLocale($locale);

                $message = $this->renderView(
                    'emails/reservation_confirm.html.twig',
                    array(
                        'loanRows'    => $loan->getLoanRows()
                    )
                );

                $client->sendEmail(
                    "{$senderName} <hello@lend-engine.com>",
                    $toEmail,
                    $subject." (Ref ".$loan->getId().")",
                    $message,
                    null,
                    null,
                    true,
                    $senderEmail
                );

                // Revert locale for the UI
                $this->get('translator')->setLocale($sessionLocale);

                $this->addFlash('success', "Email sent OK");

            } catch (\Exception $generalException) {

                $this->addFlash('error', "Failed to send email: ".$generalException->getMessage());

            }

        }

        return $this->redirectToRoute('loan', ['id' => $loanId]);
    }

}