<?php

namespace AppBundle\Controller\Item;

use AppBundle\Entity\Image;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\Note;
use AppBundle\Entity\ProductField;
use AppBundle\Entity\ProductFieldValue;
use AppBundle\Entity\ProductTag;
use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;
use Doctrine\ORM\EntityRepository;
use AppBundle\Form\Type\ItemType;

class ItemCopyController extends Controller
{
    protected $id;

    /**
     * @Route("admin/item/{id}/copy", name="item_copy", requirements={"id": "\d+"})
     */
    public function copyProduct($id)
    {

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        /** @var $oldProduct \AppBundle\Entity\InventoryItem */
        $oldProduct = $em->getRepository('AppBundle:InventoryItem')->find($id);

        $newProduct = clone $oldProduct;
        $newProduct->setId(null);

        $newProduct->setCreatedBy($user);

        /** @var \AppBundle\Entity\InventoryLocation $location */
        $locationRepo = $em->getRepository('AppBundle:InventoryLocation');
        $location = $locationRepo->find(2);

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryItem($newProduct);
        $transactionRow->setInventoryLocation($location);
        $transactionRow->setCreatedBy($user);

        $em->persist($transactionRow);

        $newProduct->setInventoryLocation($location);
        $newProduct->setImageName(null);

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setText('Added item to <strong>'.$location->getName().'</strong>');
        $note->setInventoryItem($newProduct);
        $em->persist($note);

        // Set initial field value if auto-sku is turned on
        $skuStub = $this->get('tenant_information')->getCodeStub();
        if ($skuStub) {
            $sku = $this->generateAutoSku($skuStub);
            $newProduct->setSku($sku);
        }

        // Update the ID of the translations
        $locales = explode(',', $this->get('settings')->getSettingValue('org_languages'));

        $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
        $translatableFields = ['name', 'description', 'componentInformation', 'careInformation'];
        $translations = $repository->findTranslations($oldProduct);
        foreach ($translatableFields AS $fieldKey) {
            foreach ($locales AS $lang) {
                if (isset($translations[$lang][$fieldKey])) {
                    $val = $translations[$lang][$fieldKey];
                } else {
                    $val = '';
                }
                $repository->translate($newProduct, $fieldKey, $lang, $val);
            }
        }

        // if we don't have translated data we store in the product record:
        $newProduct->setName($oldProduct->getName());
        $newProduct->setCareInformation($oldProduct->getCareInformation());
        $newProduct->setDescription($oldProduct->getDescription());
        $newProduct->setComponentInformation($oldProduct->getComponentInformation());

        // Clear the serial number
        $newProduct->setSerial('');

        // Copy all images
        foreach($oldProduct->getImages() AS $image) {
            /** @var $image \AppBundle\Entity\Image */
            $newImageName = $this->copyImage($image->getImageName());

            $newImage = new Image();
            $newImage->setImageName($newImageName);
            $newImage->setInventoryItem($newProduct);

            // Images are set to cascade persist when we save an item
            $newProduct->addImage($newImage);

            // Set as primary
            $newProduct->setImageName($newImageName);
        }

        $em->persist($newProduct);

        try {
            $em->flush();
            $this->addFlash('success', "Item copied.");
            return $this->redirectToRoute('item', array('id' => $newProduct->getId()));
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Item failed to copy.');
            $this->addFlash('debug', $generalException->getMessage());
        }

        return $this->redirectToRoute('item', array('id' => $oldProduct->getId()));

    }

    private function copyImage($imageName)
    {
        $filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');
        $schema = $this->get('tenant_information')->getSchema();
        $newImageName = 'a'.$imageName;

        // Copy the thumbnail
        $originalPath = $schema.'/thumbs/'.$imageName;
        $newPath = $schema.'/thumbs/'.$newImageName;
        $filesystem->copy($originalPath, $newPath);

        // Copy the large
        $originalPath = $schema.'/large/'.$imageName;
        $newPath = $schema.'/large/'.$newImageName;
        $filesystem->copy($originalPath, $newPath);

        return $newImageName;
    }

    /**
     *
     * THIS IS REPLICATED IN ItemController
     * @todo move to inventory or item service
     *
     * This won't work at high throughput; it's not transactional
     * Also assumes that user has got all 4-digit SKUs; will break with 3 digits
     * unless we add the REGEX doctrine extension to only search for latest 4-digit code
     * @param $stub
     * @return string
     */
    private function generateAutoSku($stub)
    {
        $lastSku = $stub;

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');
        $builder = $itemRepo->createQueryBuilder('i');
        $builder->add('select', 'MAX(i.sku) AS sku');
        $builder->where("i.sku like '{$stub}%' AND i.isActive = 1");
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $lastSku = $results[0]['sku'];
        }
        $id = (int)str_replace($stub, '', $lastSku);
        $id++;
        $newSku = $stub.str_pad($id, 4, '0', STR_PAD_LEFT);
        return $newSku;
    }

}