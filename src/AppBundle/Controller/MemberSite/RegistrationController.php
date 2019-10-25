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

        if (!$contact->getEmail()) {
            return true;
        }

        if ($locale = $contact->getLocale()) {
            $session = $request->getSession();
            $session->set('_locale', $locale);
        }

        $apiKey = $this->container->get('settings')->getSettingValue('mailchimp_api_key');
        $listId = $this->container->get('settings')->getSettingValue('mailchimp_default_list_id');
        $doubleOptIn = $this->container->get('settings')->getSettingValue('mailchimp_double_optin');

        $addedToMailchimp = false;
        if ($apiKey && $listId && $contact->getSubscriber() == true) {

            $mailchimp = $this->get('hype_mailchimp');
            $mailchimp->setApiKey($apiKey);
            $mailchimp->setListID($listId);

            $mergeVars = [
                'mc_location' => [
                    'latitude'  => $contact->getLatitude(),
                    'longitude' => $contact->getLongitude()
                ],
                'fname' => $contact->getFirstName(),
                'lname' => $contact->getLastName()
            ];
            $mailchimp->getList()->addMerge_vars($mergeVars)->subscribe($contact->getEmail(), 'html', $doubleOptIn, true);

            $addedToMailchimp = true;
        }

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
        try {

            $senderName     = $tenantService->getCompanyName();
            $replyToEmail   = $tenantService->getReplyToEmail();
            $fromEmail      = $tenantService->getSenderEmail();
            $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

            $client = new PostmarkClient($postmarkApiKey);
            $ownerEmail = $tenantService->getCompanyEmail();

            $extra = '';
            if ($addedToMailchimp) {
                $extra .= PHP_EOL.'The user was added to your Mailchimp email list.';
            }

            if ($addedMembershipType) {
                $extra .= PHP_EOL."The user was subscribed as a {$addedMembershipType} member.";
            }

            $message = $this->renderView(
                'emails/template.html.twig',
                array(
                    'heading' => "Your library is growing!",
                    'message' => "A new contact has confirmed their email address on your Lend Engine site".PHP_EOL.PHP_EOL.$contact->getName().PHP_EOL.$contact->getEmail().PHP_EOL.$extra
                )
            );

            $client->sendEmail(
                "{$senderName} <{$fromEmail}>",
                $ownerEmail,
                "New registration on your member site : ".$contact->getName(),
                $message,
                null,
                null,
                true,
                $replyToEmail
            );

        } catch (PostmarkException $ex) {
            $this->addFlash('error', 'Failed to send email:' . $ex->message . ' : ' . $ex->postmarkApiErrorCode);
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
        }

        return $this->render('member_site/registration_welcome.html.twig', [
            'chooseMembership' => $proceedToChooseMembership
        ]);

    }

}
