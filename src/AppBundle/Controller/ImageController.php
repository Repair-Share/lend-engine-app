<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ImageController extends Controller
{

    /**
     * @return Response
     * @Route("admin/item/{id}/image/{name}/remove/", name="image_remove")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function removeImageAction($id, $name)
    {

        $em = $this->getDoctrine()->getManager();

        $schema   = $this->get('service.tenant')->getSchema();

        /** @var \AppBundle\Repository\ImageRepository $repo */
        $repo   = $em->getRepository('AppBundle:Image');
        $images = $repo->findBy(['imageName' => $name]);

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        // Remove the files from S3
        $filesystem = $this->get('oneup_flysystem.product_image_fs_filesystem');
        $thumbPath = $schema.'/thumbs/'.$name;
        $largePath = $schema.'/large/'.$name;

        try {
            $filesystem->delete($thumbPath);
            $filesystem->delete($largePath);
        } catch (\Exception $e) {

        }

        // Update all items that reference this image.
        // Will leave items without a primary thumbnail name.
        /** @var \AppBundle\Entity\InventoryItem $item */
        $items = $itemRepo->findBy(['imageName' => $name]);
        foreach ($items AS $item) {
            $item->setImageName("");
            $em->persist($item);
        }

        // Remove all references to this image
        foreach ($images AS $image) {
            $em->remove($image);
        }

        $em->flush();
        $msg = 'ok';

        return new Response(json_encode($msg));
    }

    /**
     * @return Response
     * @Route("admin/image/{name}/rotate/", name="image_rotate")
     * @Security("has_role('ROLE_ADMIN')")
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