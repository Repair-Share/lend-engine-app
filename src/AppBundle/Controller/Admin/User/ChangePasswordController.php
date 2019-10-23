<?php

namespace AppBundle\Controller\Admin\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ChangePasswordController extends Controller
{

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