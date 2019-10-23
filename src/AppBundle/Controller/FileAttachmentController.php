<?php

/**
 * Retrieve a file from a private AWS S3 bucket and send to the browser
 * Required so that S3 buckets are not public
 *
 */
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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

}