<?php

/**
 * Deal with files uploaded to items or contacts
 */
namespace AppBundle\EventListener;

use AppBundle\Entity\FileAttachment;
use AppBundle\Entity\Image;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Oneup\UploaderBundle\Event\PostPersistEvent;
use Symfony\Component\DependencyInjection\Container;

class UploadListenerLocalStorage
{
    /** @var EntityManager  */
    private $em;

    /** @var Container  */
    private $container;

    /** @var SettingsService */
    private $settings;

    private $logger;

    public function __construct(EntityManager $em, Container $container, SettingsService $settings, \Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->container = $container;
        $this->settings = $settings;
    }

    public function onUpload(PostPersistEvent $event)
    {
        //$s3_bucket = $this->container->get('service.tenant')->getS3Bucket();
        $s3_bucket = "";
        $schema    = $this->container->get('service.tenant')->getSchema();
        $this->logger->info("s3 bucket " . $s3_bucket);
        $this->logger->info("schema " . $schema);

        $request  = $event->getRequest();
        $response = $event->getResponse();

        /** @var $file */
        $file = $event->getFile();
        $fileName = $file->getBasename();

        if ($itemId = $request->get('itemId')) {
            $this->logger->info("itemId " . $itemId);
            $this->logger->info("uploadType " . $request->get('uploadType'));

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
                    $this->logger->info("file basename " . $file->getBasename());

                    $this->em->persist($item);
                    $this->em->flush();

                    $fullFilePath = $s3_bucket.$file->getPathname();
                    $thumb_path = $schema.'/thumbs/';
                    $large_path = $schema.'/large/';
                    $this->logger->info("thumb_path " . $thumb_path);
                    $this->logger->info("large_path " . $large_path);

                    // Create a thumbmail
                    $this->container->get('helper.imageresizer')->resizeImage($fullFilePath, $thumb_path, 100, 100);

                    // Resize the original to something sensible
                    $this->container->get('helper.imageresizer')->resizeImage($fullFilePath, $large_path, 600, 600);

                    // Remove the original
                    //$filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');
                    //$filesystem->delete( $file->getPathname() );

                    $response['newFileName'] = $file->getBasename();

                } catch (\Exception $e) {
                    die( $e->getMessage() );
                }

            }

        } else if ($maintenanceId = $request->get('maintenanceId')) {

            $this->logger->info("maintenanceId " . $maintenanceId);

            /** @var \AppBundle\Entity\Maintenance $maintenance */
            $maintenance = $this->em->getRepository('AppBundle:Maintenance')->find($maintenanceId);

            $fileAttachment = new FileAttachment();
            $fileAttachment->setMaintenance($maintenance);
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

        } else if ($contactId = $request->get('contactId')) {

            $this->logger->info("contactId " . $contactId);

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

            $this->logger->info("uploadType " . $request->get('uploadType'));

            $this->settings->setSettingValue('logo_image_name', $fileName);
            $response['fileName'] = $fileName;

        } else if ($request->get('uploadType') == 'site_images') {

            $this->logger->info("uploadType " . $request->get('uploadType'));

            // From page editor image upload
            // Return the uploaded image URL
            $response['url'] = $s3_bucket.$schema.'/site_images/'.$file->getBasename();

        }

    }
}