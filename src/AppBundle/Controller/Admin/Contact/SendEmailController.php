<?php

namespace AppBundle\Controller\Admin\Contact;

use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SendEmailController extends Controller
{

    /**
     * @Route("admin/contact/{contactId}/send-email", name="send_email", requirements={"contactId": "\d+"})
     */
    public function sendEmail(Request $request, $contactId)
    {

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->container->get('service.contact');

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $em = $this->getDoctrine()->getManager();

        if (!$user = $this->getUser()) {
            return $this->redirectToRoute('contact', ['id' => $contactId]);
        }

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');
        if (!$contact = $contactRepo->find($contactId)) {
            return $this->redirectToRoute('contact_list');
        }

        $loanId    = $request->get('loan_id');
        $includeButton = $request->get('include_button');
        $replyToEmail   = $tenantService->getReplyToEmail();

        if ($request->get('email_body') != '') {

            $messageText = $request->get('email_body');
            $messageSubject = $request->get('email_subject');

            $token = $contactService->generateAccessToken($contact);
            $loginUri = $tenantService->getTenant(false)->getDomain(true);
            $loginUri .= '/access?t='.$token.'&e='.urlencode($contact->getEmail());
            $loginUri .= '&r=/profile/';

            $message = $this->renderView(
                'emails/template.html.twig',
                [
                    'heading' => '',
                    'message' => $messageText.PHP_EOL,
                    'includeButton' => $includeButton,
                    'loginUri' => $loginUri
                ]
            );

            /** @var \AppBundle\Entity\Note $note */
            $note = new Note();
            $note->setCreatedBy($user);
            $note->setContact($contact);
            $note->setText("Sent email '{$messageSubject}'.");
            $em->persist($note);
            $em->flush();

            if ($emailService->send($contact->getEmail(), $contact->getName(), $messageSubject, $message, 'always')) {
                $this->addFlash('success', "Sent email to ".$contact->getEmail());
            } else {
                $this->addFlash('error', 'Failed to send email to '.$contact->getEmail());
            }

            if ($loanId) {
                return $this->redirectToRoute('public_loan', ['loanId' => $loanId]);
            } else {
                return $this->redirectToRoute('contact', ['id' => $contactId]);
            }

        }

        return $this->render(
            'modals/email.html.twig',
            [
                'contactId' => $contactId,
                'loanId' => $loanId,
                'replyTo' => $replyToEmail,
                'pageTitle' => "Send an email to ".$contact->getName().' ('.$contact->getEmail().')'
            ]
        );

    }

}