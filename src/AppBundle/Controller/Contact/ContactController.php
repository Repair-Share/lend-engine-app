<?php

namespace AppBundle\Controller\Contact;

use AppBundle\Entity\Child;
use AppBundle\Entity\Contact;
use AppBundle\Entity\ContactFieldValue;
use AppBundle\Entity\Note;
use Doctrine\Common\Collections\ArrayCollection;
use Hype\MailchimpBundle\Mailchimp\MailchimpAPIException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;
use Doctrine\ORM\EntityRepository;
use AppBundle\Form\Type\ContactType;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

class ContactController extends Controller
{

    /**
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("admin/contact/{id}", name="contact", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function contactAction(Request $request, $id = 0)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var $billingService \AppBundle\Services\BillingService */
        $billingService = $this->get('billing');

        /** @var \AppBundle\Entity\Contact $contact */

        if ($id) {

            $contact = $contactRepo->find($id);
            if (!$contact) {
                throw $this->createNotFoundException(
                    'No contact found for id '.$id
                );
            }
            $pageTitle = $contact->getFirstName().' '.$contact->getLastName();

        } else {

            // Check to see if user has exceeded contact count

            $plan = $this->get('session')->get('plan');
            $maxContacts = $billingService->getMaxContacts($plan);

            $count = $contactRepo->countActiveContacts();
            if ($count >= $maxContacts) {
                $this->addFlash('error', "You've reached the maximum number of contacts allowed on your plan ($maxContacts). Please archive some contacts (open the contact then click 'archive') or upgrade via the billing screen.");
                return $this->redirectToRoute('contact_list');
            }

            if ($request->get('next') == 'membership') {
                $pageTitle = 'Add a new member';
            } else {
                $pageTitle = 'Add a new contact';
            }

            $manager = $this->get('fos_user.user_manager');
            $contact = $manager->createUser();

            $contact->setCreatedBy($user);

            if ($site = $user->getActiveSite()) {
                $contact->setCreatedAtSite($site);
            }

            $plainPassword = $this->generatePassword();
            $contact->setPlainPassword($plainPassword);

            $contact->addRole("ROLE_USER");
            $contact->setEnabled(true);

            $countryIsoCode = $this->get('settings')->getSettingValue('org_country');
            $contact->setCountryIsoCode($countryIsoCode);

            $defaultLocale = $this->get('settings')->getSettingValue('org_locale');
            $contact->setLocale($defaultLocale);

            $note = new Note();
            $note->setContact($contact);
            $note->setCreatedBy($user);
            $note->setText("Added by ".$user->getName());
            $em->persist($note);

        }

        $originalChildren = new ArrayCollection();
        if (is_array($contact->getChildren()) && count($contact->getChildren()) > 0) {
            foreach ($contact->getChildren() as $child) {
                $originalChildren->add($child);
            }
        }

        /** @var \AppBundle\Repository\ContactFieldRepository $fieldRepo */
        $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ContactField');

        if ($this->get('service.tenant')->getFeature('ProductField')) {
            $customFields = $fieldRepo->findAllOrderedBySort();
            $customFieldValues = $contact->getFieldValues();
        } else {
            $customFields = [];
            $customFieldValues = [];
        }

        if ($this->get('settings')->getSettingValue('mailchimp_api_key')) {
            $showSubscriberField = true;
        } else {
            $showSubscriberField = false;
        }

        $options = [
            'customFields' => $customFields,
            'customFieldValues' => $customFieldValues,
            'authorizationChecker' => $this->get('security.authorization_checker'),
            'showSubscriberField' => $showSubscriberField
        ];
        $form = $this->createForm(ContactType::class, $contact, $options);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // So that the user can log in
            if ($emailAddress = $form->get('email')->getData()) {
                $contact->setUsername($emailAddress);
            }

            // Delete any children removed from the contact
            foreach ($originalChildren as $child) {
                if (false === $contact->getChildren()->contains($child)) {
                     $em->remove($child);
                }
            }

            $contactFieldValues = array();
            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ContactField */
                $i = $field->getId();

                if ( $form->has('fieldValue'.$i) ) {

                    $newFieldValue = $form->get('fieldValue'.$i)->getData();
                    if (is_array($newFieldValue)) {
                        $newFieldValue = implode(',', $newFieldValue);
                    }

                    if (isset($customFieldValues[$i])) {
                        // UPDATE
                        /** @var \AppBundle\Entity\ContactFieldValue $fieldValue */
                        $fieldValue = $customFieldValues[$i];
                        $fieldValue->setFieldValue($newFieldValue);
                        $contactFieldValues[] = $fieldValue;
                    } else {
                        // CREATE
                        $contactField = $fieldRepo->find($i);
                        $fieldValue = new ContactFieldValue();
                        $fieldValue->setContact($contact);
                        $fieldValue->setContactField($contactField);
                        $fieldValue->setFieldValue($newFieldValue);
                        $contactFieldValues[] = $fieldValue;
                    }
                }
            }
            $contact->setFieldValues($contactFieldValues);

            $plainPassword= '';
            if ($form->has('autoPassword') && $form->get('autoPassword')->getData() == 1) {
                $plainPassword = $this->generatePassword();
                $contact->setPlainPassword($plainPassword);
            } else if ($form->has('sendWelcomeEmail') && $form->get('sendWelcomeEmail')->getData() == 1) {
                $plainPassword = $this->generatePassword();
                $contact->setPlainPassword($plainPassword);
            }

            $em->persist($contact);

            try {
                $em->flush();

                // Add / update contact in Mailchimp
                $this->mailChimpSubscribe($contact);

                if ($form->has('autoPassword') && $form->get('autoPassword')->getData() == 1 && $contact->getEmail()) {
                    // Send the welcome email
                    $this->sendWelcomeEmail($contact, $plainPassword);
                } else if ($form->has('sendWelcomeEmail') && $form->get('sendWelcomeEmail')->getData() == 1 && $contact->getEmail()) {
                    // Send the welcome email
                    $this->sendWelcomeEmail($contact, $plainPassword);
                }

                $this->addFlash('success', 'Contact saved.');

            } catch (\Exception $generalException) {
                $this->addFlash('error', $generalException->getMessage());
            }

            if ($request->get('form_action') == 'saveAndNext') {
                return $this->redirectToRoute('contact', array('id' => ($contact->getId()+1)));
            } else if ($request->get('form_action') == 'saveAndAddLoan') {
                return $this->redirectToRoute('basket_create', ['contactId' => $contact->getId()]);
            } else if ($newId = $contact->getId()) {
                if ($request->get('next') == 'membership') {
                    // Automatically open the membership modal
                    return $this->redirectToRoute('contact', array('id' => $newId, 'open' => 'membership'));
                } else {
                    return $this->redirectToRoute('contact', array('id' => $newId));
                }
            } else {
                $this->addFlash('error', 'There was an error creating the contact.');
            }

        }

        if (count($customFields) > 0) {
            $customFieldsExist = true;
        } else {
            $customFieldsExist = false;
        }

        // Hide non-admin notes if appropriate
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_USER')) {
            foreach ($contact->getNotes() AS $note) {
                if ($note->getAdminOnly()) {
                    $contact->removeNote($note);
                }
            }
        }

        return $this->render('contact/contact.html.twig', array(
            'form' => $form->createView(),
            'title' => $pageTitle,
            'customFieldsExist' => $customFieldsExist,
            'customFields' => $customFields,
            'contact' => $contact
        ));
    }

    /**
     * @param Contact $contact
     * @param $plainPassword
     * @return bool
     */
    private function sendWelcomeEmail(Contact $contact, $plainPassword)
    {
        $locale = $contact->getLocale();

        if (!$subject = $this->get('settings')->getSettingValue('email_welcome_subject')) {
            $subject = $this->get('translator')->trans('le_email.site_welcome.subject', ['%accountName%' => $this->get('service.tenant')->getCompanyName()], 'emails', $locale);
        }

        $senderName = $this->get('service.tenant')->getCompanyName();
        $senderEmail = $this->get('service.tenant')->getCompanyEmail();

        try {
            $client = new PostmarkClient($this->getParameter('postmark_api_key'));

            // Save and switch locale for sending the email
            $sessionLocale = $this->get('translator')->getLocale();
            $this->get('translator')->setLocale($locale);

            $message = $this->renderView(
                'emails/site_welcome.html.twig',
                array(
                    'email'       => $contact->getEmail(),
                    'password'    => $plainPassword
                )
            );

            $client->sendEmail(
                "{$senderName} <hello@lend-engine.com>",
                $contact->getEmail(),
                $subject,
                $message,
                null,
                null,
                true,
                $senderEmail
            );

            // Revert locale for the UI
            $this->get('translator')->setLocale($sessionLocale);

            $this->addFlash('success', " We've sent a welcome email to " . $contact->getEmail() . ".");

        } catch (PostmarkException $ex) {
            $this->addFlash('error', 'Failed to send email:' . $ex->message . ' : ' . $ex->postmarkApiErrorCode);
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
        }

        return true;
    }

    /**
     * JSON responder for select menu
     * @Route("admin/select/contact/list", name="select_contact")
     */
    public function selectMenuContactList(Request $request)
    {
        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $search = $request->get('q');
        $filter = array(
            'search' => $search
        );
        $searchResults = $contactService->contactSearch(0, 50, $filter);

        $contacts = $searchResults['data'];

        $data = array();
        foreach ($contacts AS $contact) {
            /** @var \AppBundle\Entity\Contact $contact */
            $contactName = $contact->getFirstName().' '.$contact->getLastName();
            if ($contact->getBalance() != 0) {
                $contactName .= ' ('.$contact->getBalance().' on account)';
            }
            $data[] = array(
                'id'   => $contact->getId(),
                'text' => $contactName
            );
        }
        return new Response(
            json_encode($data),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * @return string
     */
    private function generatePassword()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param Contact $contact
     * @return bool
     */
    private function mailChimpSubscribe(Contact $contact)
    {

        if (!$contact->getEmail()) {
            return true;
        }

        $apiKey = $this->get('settings')->getSettingValue('mailchimp_api_key');
        $listId = $this->get('settings')->getSettingValue('mailchimp_default_list_id');
        $doubleOptIn = $this->get('settings')->getSettingValue('mailchimp_double_optin');

        if ($apiKey && $listId && $contact->getSubscriber() == true) {

            /** @var \Hype\MailchimpBundle\Mailchimp\Mailchimp $mailchimp */
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

            try {
                $mailchimp->getList()->addMerge_vars($mergeVars)->subscribe($contact->getEmail(), 'html', $doubleOptIn, true);
            } catch (\Hype\MailchimpBundle\Mailchimp\MailchimpAPIException $mailchimpException) {
                $this->addFlash('error', 'Failed to subscribe to Mailchimp:' . $mailchimpException->getMessage());
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'Failed to subscribe to Mailchimp:' . $generalException->getMessage());
            }

        }

        return true;
    }

}