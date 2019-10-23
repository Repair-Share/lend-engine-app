<?php

namespace AppBundle\Controller\Admin\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class UpdateFileController extends Controller
{

    /**
     * @return Response
     * @Route("admin/file/{fileId}/sendToMemberToggle/", name="toggle_send")
     */
    public function sendToMemberToggleAction($fileId)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\FileAttachmentRepository $repo */
        $repo = $em->getRepository('AppBundle:FileAttachment');

        /** @var \AppBundle\Entity\FileAttachment $file */
        $file = $repo->find($fileId);
        $files = $repo->findBy(['fileName' => $file->getFileName()]);

        foreach ($files AS $file) {
            if ($file->getSendToMemberOnCheckout()) {
                $file->setSendToMemberOnCheckout(false);
            } else {
                $file->setSendToMemberOnCheckout(true);
            }

            $em->persist($file);
        }

        $em->flush();

        $msg = 'ok';
        return new Response(json_encode($msg));

    }


}