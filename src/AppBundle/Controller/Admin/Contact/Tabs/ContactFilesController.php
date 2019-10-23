<?php

namespace AppBundle\Controller\Admin\Contact\Tabs;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

class ContactFilesController extends Controller
{

    /**
     * @Route("admin/contact/{id}/files", name="contact_files", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function itemFilesAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        $contact = $contactRepo->find($id);

        return $this->render(
            'contact/tabs/contact_files.html.twig',
            array(
                'contact' => $contact,
            )
        );

    }

}