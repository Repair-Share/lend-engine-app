<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
/**
 * Class RegistrationController
 * @package AppBundle\Controller\MemberSite
 */
class RegistrationController extends Controller
{

    /**
     *
     * The page the user sees when they confirm their email address
     * Allows us to set the locale into the session and subscribe to mailchimp
     *
     * @param Request $request
     * @return Response
     * @Route("/member/welcome", name="registration_welcome")
     */
    public function registrationWelcome(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Entity\Contact $contact */
        $contact = $this->getUser();

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        if (!$contact->getEmail()) {
            return $this->redirectToRoute('home');
        }

        if ($locale = $contact->getLocale()) {
            $session = $request->getSession();
            $session->set('_locale', $locale);
        }

        /** @var \AppBundle\Services\Apps\MailchimpService $mailchimp */
        $mailchimp = $this->get('service.mailchimp');
        $mailchimp->updateMember($contact);

        // To deal repeat visits to the URL, only sign up once
        $addedMembershipType = false;

        $proceedToChooseMembership = false;
        if (!$contact->getActiveMembership()) {
            // Auto-enrol in any self serve membership
            /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
            $membershipTypeRepo = $em->getRepository('AppBundle:MembershipType');

            $selfServeMembershipTypes = $membershipTypeRepo->findBy(['isSelfServe' => true]);
            if (count($selfServeMembershipTypes) > 0) {
                $proceedToChooseMembership = true;
            }
        }

        // Send an email to admin
        $ownerEmail = $tenantService->getCompanyEmail();
        $ownerName = $tenantService->getAccountName();

        $extra = '';
        if ($addedMembershipType) {
            $extra .= PHP_EOL."The user was subscribed as a {$addedMembershipType} member.";
        }

        $message = $this->renderView(
            'emails/template.html.twig',
            array(
                'heading' => "Your library is growing!",
                'message' => "A new contact has registered via your Lend Engine site.".PHP_EOL.PHP_EOL.$contact->getName().PHP_EOL.$contact->getEmail().PHP_EOL.$extra
            )
        );

        // Send the email without showing member any failures
        $subject = "New registration on your member site : ".$contact->getName();
        $emailService->send($ownerEmail, $ownerName, $subject, $message, false);

        return $this->render('member_site/registration_welcome.html.twig', [
            'chooseMembership' => $proceedToChooseMembership
        ]);

    }

}
