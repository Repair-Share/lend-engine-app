<?php

namespace AppBundle\Controller\Admin\Image;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RotateImageController extends Controller
{

    /**
     * @return Response
     * @Route("admin/image/{name}/rotate/", name="image_rotate")
     */
    public function rotateImageAction($name, Request $request)
    {
        $service = $this->get('helper.imageresizer');

        $s3_bucket = $this->get('service.tenant')->getS3Bucket();
        $schema    = $this->get('service.tenant')->getSchema();

        if ($request->get('rotate') == 'left') {
            $direction = 'left';
        } else {
            $direction = 'right';
        }

        $thumbPath = $s3_bucket.$schema.'/thumbs/'.$name;
        $largePath = $s3_bucket.$schema.'/large/'.$name;

        $service->rotateImage($thumbPath, $schema.'/thumbs/', $direction);
        $service->rotateImage($largePath, $schema.'/large/', $direction);

        $msg = 'ok';
        return new Response(json_encode($msg));
    }


}