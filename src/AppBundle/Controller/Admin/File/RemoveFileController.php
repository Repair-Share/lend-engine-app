<?php

namespace AppBundle\Controller\Admin\File;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

class RemoveFileController extends Controller
{

    /**
     * @return Response
     * @Route("admin/file/{fileId}/remove/", name="file_remove")
     */
    public function removeFileAction($fileId)
    {

        $em = $this->getDoctrine()->getManager();
        $schema = $this->get('service.tenant')->getSchema();

        /** @var \AppBundle\Repository\FileAttachmentRepository $repo */
        $repo = $em->getRepository('AppBundle:FileAttachment');
        $file = $repo->find($fileId);

        $fileName = $file->getFileName();
        try {
            /** @var \AppBundle\Entity\FileAttachment $file */
            $em->remove($file);
            $em->flush();
        } catch (\Exception $e) {

        }

        $msg = 'ok';

        try {
            // Remove the file from S3
            $filesystem = $this->container->get('oneup_flysystem.secure_file_fs_filesystem');
            $filePath = $schema.'/files/'.$fileName;
            $filesystem->delete($filePath);
        } catch (\Exception $e) {
        }

        return new Response(json_encode($msg));

    }

}