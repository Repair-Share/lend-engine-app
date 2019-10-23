<?php

namespace AppBundle\Controller\Admin\Image;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveImageController extends Controller
{

    /**
     * @return Response
     * @Route("admin/item/{id}/image/{name}/remove/", name="image_remove")
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

}