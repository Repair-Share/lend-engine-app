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
        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $this->getDoctrine()->getRepository('AppBundle:Loan')->find($loanId)) {
            $this->addFlash('error', "Could not find loan ID {$loanId}");
            return $this->redirectToRoute('loan_list');
        }

        $senderName     = $tenantService->getCompanyName();
        $replyToEmail   = $tenantService->getReplyToEmail();
        $fromEmail      = $tenantService->getSenderEmail();
        $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

        $locale = $loan->getContact()->getLocale();

        // Send email confirmation
        if ($toEmail = $loan->getContact()->getEmail()) {

            if (!$subject = $this->get('settings')->getSettingValue('email_reserve_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.reservation_confirm.subject', [], 'emails', $locale);
            }

            try {
                $client = new PostmarkClient($postmarkApiKey);

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
                    "{$senderName} <{$fromEmail}>",
                    $toEmail,
                    $subject." (Ref ".$loan->getId().")",
                    $message,
                    null,
                    null,
                    true,
                    $replyToEmail
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