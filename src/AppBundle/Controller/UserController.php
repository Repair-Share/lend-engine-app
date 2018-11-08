<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserController extends Controller
{

    /**
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("admin/user/{id}", name="user", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function userFormAction(Request $request, $id = 0)
    {
        $userManager = $this->container->get('fos_user.user_manager');

        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        /** @var $user \AppBundle\Entity\Contact */
        if ($id) {
            $user = $this->getDoctrine()->getRepository('AppBundle:Contact')->find($id);
            if (!$user) {
                throw $this->createNotFoundException(
                    'No user found for id '.$id
                );
            }
            $pageTitle = 'User '.$user->getName();
            $flashMessage = 'User updated.';
        } else {
            // Creating a new user
            $pageTitle = 'Add a new user';
            $flashMessage = 'User created.';

            $user = $userManager->createUser();
            $user->setEnabled(true);
        }

        $formBuilder = $this->createFormBuilder($user,
            [
                'action' => $this->generateUrl('user', array('id' => $id))
            ]
        );

        $formBuilder->add('firstName', TextType::class, array(
            'label' => 'First name'
        ));

        $formBuilder->add('lastName', TextType::class, array(
            'label' => 'Last name'
        ));

        $formBuilder->add('email', EmailType::class, array(
            'label' => 'Email address',
            'attr' => array(
                'data-help' => ''
            )
        ));

        if ($id) {
            $formBuilder->add('autoPassword', CheckboxType::class, array(
                'label' => 'Send a new password by email',
                'mapped' => false,
                'required' => false,
                'attr' => array(
                    'data-help' => ''
                )
            ));
        } else {
            // new staff, set checkboxes on by default
            $user->addRole("ROLE_ADMIN");
            $user->addRole("ROLE_SUPER_USER");
        }

        $formBuilder->add('roles', ChoiceType::class, array(
            'choices' => array(
                'Staff member (log in to admin)' => 'ROLE_ADMIN',
                'Administrator (add items, edit settings, export)' => 'ROLE_SUPER_USER'
            ),
            'label' => 'Permissions',
            'expanded' => true,
            'multiple' => true,
            'mapped' => true,
        ));

        $formBuilder->add('enabled', CheckboxType::class, array(
            'label' => 'Active user?',
            'required' => false,
            'attr' => array(
                'data-help' => 'Un-check this to de-activate old users.'
            )
        ));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $emailAddress = $form->get('email')->getData();

            $user->setUsername( $emailAddress );
            $user->setEmail( $emailAddress );
            if ($user->hasRole("ROLE_SUPER_USER") && !$user->hasRole("ROLE_ADMIN")) {
                $user->addRole("ROLE_ADMIN");
            }

            $newPassword = '';
            $sendUserEmail = false;

            if (($form->has('autoPassword') && $form->get('autoPassword')->getData() == 1) || !$id) {
                $newPassword = $this->generatePassword();
                $user->setPlainPassword($newPassword);
                $sendUserEmail = true;
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);

            try {
                $em->flush();

                if ($sendUserEmail == true) {

                    $locale = $user->getLocale();
                    $accountName = $this->get('tenant_information')->getCompanyName();

                    if (!$subject = $this->get('settings')->getSettingValue('email_welcome_subject')) {
                        $subject = $this->get('translator')->trans('le_email.login_details.subject', ['%accountName%' => $accountName], 'emails', $locale);
                    }

                    try {
                        $client = new PostmarkClient($this->getParameter('postmark_api_key'));

                        // Save and switch locale for sending the email
                        $sessionLocale = $this->get('translator')->getLocale();
                        $this->get('translator')->setLocale($locale);

                        $message = $this->renderView(
                            'emails/login_details.html.twig',
                            array(
                                'email'       => $emailAddress,
                                'password'    => $newPassword
                            )
                        );

                        $toEmail = $emailAddress;
                        $client->sendEmail(
                            "hello@lend-engine.com",
                            $toEmail,
                            $subject,
                            $message
                        );

                        // Revert locale for the UI
                        $this->get('translator')->setLocale($sessionLocale);

                        $flashMessage .= " We've sent an email to " . $emailAddress . " with login information.";

                    } catch (PostmarkException $ex) {
                        $this->addFlash('error', 'Failed to send email:' . $ex->message . ' : ' . $ex->postmarkApiErrorCode);
                    } catch (\Exception $generalException) {
                        $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
                    }
                }

                $this->addFlash('success', $flashMessage);

                return $this->redirectToRoute('users_list');

            } catch (DBALException $e) {
                $this->addFlash('debug', $e->getMessage());
            } catch (\Exception $generalException) {
                $this->addFlash('debug', $generalException->getMessage());
            }

        }

        return $this->render('modals/user.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
            'title' => $pageTitle
        ));

    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("admin/user_delete/{id}", name="user_delete", requirements={"id": "\d+"})
     */
    public function deleteAction($id)
    {

        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        // check to see if self
        $user = $this->getUser();
        $userId = $user->getId();

        if ($userId == $id) {
            $this->addFlash('error', "You can't delete yourself. That would cause some trouble...");
        } else {
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('AppBundle:Contact')->find($id);
            $em->remove($user);
            try {
                $em->flush();
                $this->addFlash('success', "Done. Deleted user!");
            } catch (DBALException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('users_list');
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
     * @Route("admin/users/list", name="users_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction()
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_USER', null, 'Unable to access this page!');

        // Get users from the DB
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('AppBundle:Contact')->findAllStaff();

        return $this->render(
            'user/users_list.html.twig',
            array('users' => $users)
        );
    }

    /**
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("admin/user/{id}/password", name="change_password", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function changePasswordAction(Request $request, $id = 0)
    {

        $currentUser = $this->getUser();

        if ($currentUser->getId() != $id) {
            die("You can't change someone else's password.");
        }

        $userManager = $this->container->get('fos_user.user_manager');

        $user = $this->getDoctrine()->getRepository('AppBundle:Contact')->find($id);

        if (!$user) {
            throw $this->createNotFoundException(
                'No user found for id '.$id
            );
        }
        $pageTitle = 'Update password for '.$user->getName();

        $formBuilder = $this->createFormBuilder($user,
            [
                'action' => $this->generateUrl('change_password', array('id' => $id))
            ]
        );

        $formBuilder->add('new_password', PasswordType::class, array(
            'label' => 'Choose a new password',
            'mapped' => false
        ));

        $formBuilder->add('new_password_2', PasswordType::class, array(
            'label' => 'Re-type your password',
            'mapped' => false
        ));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newPassword = $form->get('new_password')->getData();

            $user->setPlainPassword($newPassword);

            try {
                $userManager->updateUser($user, true);
                $this->addFlash('success', "Updated password.");
                return $this->redirectToRoute('users_list');
            } catch (DBALException $e) {
                $this->addFlash('debug', $e->getMessage());
            } catch (\Exception $generalException) {
                $this->addFlash('debug', $generalException->getMessage());
            }

        }

        return $this->render('modals/change_password.html.twig', array(
            'form' => $form->createView(),
            'user' => $user,
            'title' => $pageTitle
        ));

    }

}