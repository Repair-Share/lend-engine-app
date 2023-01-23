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

        /** @var \AppBundle\Entity\Contact $user */
        $user = $this->getUser();

        /** @var \AppBundle\Entity\Contact $contact */
        $contact = $this->getUser();

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

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
        $autoEnrolMembership = null;
        if (!$contact->getActiveMembership()) {

            // Auto-enrol in any self serve membership
            /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
            $membershipTypeRepo = $em->getRepository('AppBundle:MembershipType');

            $selfServeMembershipTypes = $membershipTypeRepo->findBy(['isSelfServe' => true]);
            if (count($selfServeMembershipTypes) > 0) {
                $proceedToChooseMembership = true;
            }

            $selfServeActiveMembershipTypes = $membershipTypeRepo->findBy([
                'isSelfServe' => true,
                'isActive'    => true
            ]);

            $selfServeFreeAndActiveMembershipTypes = $membershipTypeRepo->findBy([
                'isSelfServe' => true,
                'price'       => 0,
                'isActive'    => true
            ]);

            // Auto-enrol if:
            // - Only one active self serve membership is available and
            // - That membership type is free
            if (count($selfServeActiveMembershipTypes) === 1 && count($selfServeFreeAndActiveMembershipTypes) === 1) {
                $autoEnrolMembership = $selfServeFreeAndActiveMembershipTypes[0];
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

        if ($autoEnrolMembership && $autoEnrolMembership->getId()) {

            $membership = new Membership();
            $membership->setContact($contact);
            $membership->setCreatedBy($user);
            $membership->setMembershipType($autoEnrolMembership);
            $membership->setPrice($autoEnrolMembership->getPrice());
            $membership->calculateStartAndExpiryDates();

            $em->persist($membership);

            $flashBags = $membership->subscribe(
                $em,
                $contact,
                $user,
                $paymentService,
                $autoEnrolMembership->getPrice(),
                $autoEnrolMembership->getPrice(),
                $request->get('paymentId'),
                $this->get('settings')->getSettingValue('stripe_payment_method')
            );

            foreach ($flashBags as $flashBag) {
                $this->addFlash($flashBag['type'], $flashBag['msg']);
            }

            $this->get('session')->set('pendingPaymentType', null);
            $contactService->recalculateBalance($membership->getContact());

            if ($user->hasRole('ROLE_ADMIN')) {
                $this->addFlash('success', 'Subscribed OK');
                return $this->redirectToRoute('contact', ['id' => $contact->getId()]);
            } else {
                $this->addFlash('success', 'Welcome! You are now a member.');
                return $this->redirectToRoute('fos_user_profile_show');
            }
        }

        return $this->render('member_site/registration_welcome.html.twig', [
            'chooseMembership' => $proceedToChooseMembership
        ]);

    }

}
