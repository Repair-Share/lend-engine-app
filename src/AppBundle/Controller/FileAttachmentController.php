<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class FileAttachmentController extends Controller
{

    /**
     * @return Response
     * @Route("admin/file/{fileName}/remove/", name="file_remove")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function removeFileAction($fileName)
    {

        $em = $this->getDoctrine()->getManager();

        $schema    = $this->get('service.tenant')->getSchema();

        /** @var \AppBundle\Repository\FileAttachmentRepository $repo */
        $repo = $em->getRepository('AppBundle:FileAttachment');
        $files = $repo->findBy(['fileName' => $fileName]);

        $fileName = [];
        try {
            /** @var \AppBundle\Entity\FileAttachment $file */
            foreach ($files AS $file) {
                $fileName = $file->getFileName();
                $em->remove($file);
            }
            $em->flush();
        } catch (\Exception $e) {

        }

        try {
            // Remove the file from S3
            $filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');
            $filePath = $schema.'/files/'.$fileName;
            $filesystem->delete($filePath);
        } catch (\Exception $e) {

        }

        $msg = 'ok';
        return new Response(json_encode($msg));

    }

    /**
     * @return Response
     * @Route("admin/file/{fileId}/sendToMemberToggle/", name="toggle_send")
     * @Security("has_role('ROLE_SUPER_USER')")
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