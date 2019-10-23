<?php

namespace AppBundle\Controller\Admin\User;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class DeleteUserController extends Controller
{

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("admin/user_delete/{id}", name="user_delete", requirements={"id": "\d+"})
     * @Security("has_role('ROLE_SUPER_USER')")
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

}