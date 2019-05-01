<?php

/**
 * Deal with files uploaded to items or contacts
 */
namespace AppBundle\EventListener;

use AppBundle\Entity\FileAttachment;
use AppBundle\Entity\Image;
use AppBundle\Settings\Settings;
use Doctrine\ORM\EntityManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Symfony\Component\DependencyInjection\Container;

class UploadListener
{
    /** @var EntityManager  */
    private $em;

    /** @var Container  */
    private $container;

    /** @var Settings  */
    private $settings;

    public function __construct(EntityManager $em, Container $container, Settings $settings)
    {
        $this->em = $em;
        $this->container = $container;
        $this->settings = $settings;
    }

    public function onUpload(PostPersistEvent $event)
    {

        $s3_bucket = $this->container->get('tenant_information')->getS3Bucket();
        $schema    = $this->container->get('tenant_information')->getSchema();

        $request  = $event->getRequest();
        $response = $event->getResponse();

        /** @var $file */
        $file = $event->getFile();
        $fileName = $file->getBasename();

        if ($itemId = $request->get('itemId')) {

            /** @var \AppBundle\Entity\InventoryItem $item */
            $item = $this->em->getRepository('AppBundle:InventoryItem')->find($itemId);

            if ($request->get('uploadType') == 'attachment') {

                $fileAttachment = new FileAttachment();
                $fileAttachment->setInventoryItem($item);
                $fileAttachment->setFileName($fileName);
                $fileAttachment->setFileSize($file->getSize());

                $this->em->persist($fileAttachment);

                try {

                    $this->em->flush();

                    $response['fileName'] = $fileName;
                    $response['fileId'] = $fileAttachment->getId();
                    $response['fileSize'] = $fileAttachment->getFileSize();

                } catch (\Exception $e) {
                    die($e->getMessage());
                }

            } else {

                $image = new Image();
                $image->setInventoryItem($item);
                $image->setImageName($fileName);

                // Set as main image if it's the first
                if (!$item->getImageName()) {
                    $item->setImageName($fileName);
                }
                $this->em->persist($image);

                try {

                    $this->em->persist($item);
                    $this->em->flush();

                    $fullFilePath = $s3_bucket.$file->getPathname();
                    $thumb_path = $schema.'/thumbs/';
                    $large_path = $schema.'/large/';

                    // Create a thumbmail
                    $this->container->get('helper.imageresizer')->resizeImage($fullFilePath, $thumb_path, 100, 100);

                    // Resize the original to something sensible
                    $this->container->get('helper.imageresizer')->resizeImage($fullFilePath, $large_path, 600, 600);

                    // Remove the original
                    $filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');
                    $filesystem->delete( $file->getPathname() );

                    $response['newFileName'] = $file->getBasename();

                } catch (\Exception $e) {
                    die( $e->getMessage() );
                }

            }

        } else if ($contactId = $request->get('contactId')) {

            /** @var \AppBundle\Entity\Contact $contact */
            $contact = $this->em->getRepository('AppBundle:Contact')->find($contactId);

            $fileAttachment = new FileAttachment();
            $fileAttachment->setContact($contact);
            $fileAttachment->setFileName($fileName);
            $fileAttachment->setFileSize($file->getSize());

            $this->em->persist($fileAttachment);

            try {

                $this->em->flush();

                $response['fileName'] = $file->getBasename();
                $response['fileId']   = $fileAttachment->getId();
                $response['fileSize'] = $fileAttachment->getFileSize();

            } catch (\Exception $e) {
                die( $e->getMessage() );
            }

        } else if ($request->get('uploadType') == 'logo') {

            $this->settings->setSettingValue('logo_image_name', $fileName);
            $response['fileName'] = $fileName;

        } else if ($request->get('uploadType') == 'site_images') {

            // From page editor image upload
            // Return the uploaded image URL
            $response['url'] = $s3_bucket.$schema.'/site_images/'.$file->getBasename();

        }

    }
}