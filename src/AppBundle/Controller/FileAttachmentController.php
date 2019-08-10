<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileAttachmentController extends Controller
{

    /**
     * @return Response
     * @Route("file/{tenant}/{fileName}", name="download_file")
     */
    public function getFile($tenant, $fileName)
    {
        $schema = $this->get('service.tenant')->getSchema();
        $filesystem = $this->container->get('oneup_flysystem.secure_file_fs_filesystem');

        if ($schema != $tenant) {
            return new JsonResponse(["File not found for {$tenant}"]);
        }

        $path = $schema.'/files/'.$fileName;
        if ($fileContent = $filesystem->read($path)) {
            // Return a response with a specific content
            $response = new Response($fileContent);

            // Create the disposition of the file
            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );

            // Set the content disposition
            $response->headers->set('Content-Disposition', $disposition);

            // Dispatch request
            return $response;
        } else {
            return new JsonResponse(["File not found at {$path}"]);
        }

    }

    /**
     * @return Response
     * @Route("admin/file/{fileId}/remove/", name="file_remove")
     * @Security("has_role('ROLE_SUPER_USER')")
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