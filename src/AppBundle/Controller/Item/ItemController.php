<?php

namespace AppBundle\Controller\Item;

use AppBundle\Entity\FileAttachment;
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

class ItemController extends Controller
{
    protected $id;

    /**
     * @Route("admin/item/{id}", name="item", defaults={"id" = 0}, requirements={"id": "\d+"})
     */
    public function itemAction(Request $request, $id)
    {
        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $skuStub = $this->get('service.tenant')->getCodeStub();
        $itemTypeId = null;

        if ($id) {

            // Editing item

            $product = $itemRepo->find($id);

            if (!$product) {
                throw $this->createNotFoundException(
                    'No item found for id '.$id
                );
            }

            $pageTitle = $product->getName();

            /** @var \AppBundle\Entity\ItemType $itemType */
            if ($itemType = $product->getItemType()) {
                $itemTypeId = $itemType->getId();
            }

        } else {

            // Creating item

            $pageTitle = 'Add a new item';
            $product = new InventoryItem();
            $product->setCreatedBy($user);

            /** @var \AppBundle\Entity\Site $site */
            if (!$site = $user->getActiveSite()) {
                $site = $em->getRepository('AppBundle:Site')->find(1);
            }
            if (!$site) {
                $this->addFlash('error', "We don't have a default location for site ID 1, please choose a site to work at.");
                return $this->redirectToRoute('homepage');
            }
            $defaultLocationId = $site->getDefaultCheckInLocation()->getId();

            /** @var \AppBundle\Repository\InventoryLocationRepository $locationRepo */
            $locationRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryLocation');
            $location = $locationRepo->find($defaultLocationId);
            $product->setInventoryLocation($location);

            $itemTypeId = $request->get('typeId');
            /** @var \AppBundle\Repository\ItemTypeRepository $itemTypeRepo */
            $itemTypeRepo = $this->getDoctrine()->getRepository('AppBundle:ItemType');

            if (!$itemType = $itemTypeRepo->find($itemTypeId)) {
                $this->addFlash('error', "Item type {$itemTypeId} not found");
                return $this->redirectToRoute('item_type');
            }

            $product->setItemType($itemType);

            // Set the check-in and check-out prompts which are set to "on for all new products"
            /** @var $checkInPromptRepo \AppBundle\Repository\CheckInPromptRepository */
            $checkInPromptRepo = $this->getDoctrine()->getRepository('AppBundle:CheckInPrompt');
            $checkInPrompts = $checkInPromptRepo->findAllOrderedBySort();
            foreach($checkInPrompts AS $checkInPrompt) {
                /** @var $checkInPrompt \AppBundle\Entity\CheckInPrompt */
                if ($checkInPrompt->getDefaultOn()) {
                    $product->addCheckInPrompt($checkInPrompt);
                }
            }

            /** @var $checkOutPromptRepo \AppBundle\Repository\CheckOutPromptRepository */
            $checkOutPromptRepo = $this->getDoctrine()->getRepository('AppBundle:CheckOutPrompt');
            $checkOutPrompts = $checkOutPromptRepo->findAllOrderedBySort();
            foreach($checkOutPrompts AS $checkOutPrompt) {
                /** @var $checkOutPrompt \AppBundle\Entity\CheckOutPrompt */
                if ($checkOutPrompt->getDefaultOn()) {
                    $product->addCheckOutPrompt($checkOutPrompt);
                }
            }
        }

        // Get similarly named items to update them all
        $items = $itemRepo->findBy(['name' => $product->getName(), 'isActive' => true]);

        // Set initial field value if auto-sku is turned on
        if (!$product->getSku() && $skuStub) {
            $product->setSku("{auto}");
        }

        /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
        $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');
        if ($this->get('service.tenant')->getFeature('ProductField')) {
            $customFields = $fieldRepo->findAllOrderedBySort();
            $customFieldValues = $product->getFieldValues();
        } else {
            $customFields = [];
            $customFieldValues = [];
        }

        $locales = explode(',', $this->get('settings')->getSettingValue('org_languages'));
        $formOptions = [
            'em' => $em,
            'locales' => $locales,
            'customFields' => $customFields,
            'customFieldValues' => $customFieldValues,
            'itemTypeId' => $itemTypeId // manually set as it's unmapped
        ];

        $form = $this->createForm(ItemType::class, $product, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $sku = $form->get('sku')->getData();
            if ($sku == '{auto}') {
                $newSku = $this->generateAutoSku($skuStub);
                $product->setSku($newSku);
            }

            // Clean inputs; removing tags apart from strong
            $fieldsToConvert = ['Description', 'CareInformation', 'ComponentInformation'];
            foreach ($fieldsToConvert AS $f) {
                $getter = 'get'.$f;
                $setter = 'set'.$f;
                $string = $product->$getter();
                $string = strip_tags($string,"<strong>");
                $product->$setter($string);
            }

            // Trim fields
            $fieldsToTrim = ['name', 'sku', 'serial', 'brand'];
            foreach ($fieldsToTrim AS $f) {
                $getter = 'get'.$f;
                $setter = 'set'.$f;
                $string = $product->$getter();
                $string = trim($string);
                $product->$setter($string);
            }

            $productFieldValues = array();
            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ProductField */
                $i = $field->getId();

                if ( $form->has('fieldValue'.$i) ) {

                    $newFieldValue = $form->get('fieldValue'.$i)->getData();
                    if (is_array($newFieldValue)) {
                        $newFieldValue = implode(',', $newFieldValue);
                    }

                    if (isset($customFieldValues[$i])) {
                        // UPDATE
                        /** @var \AppBundle\Entity\ProductFieldValue $fieldValue */
                        $fieldValue = $customFieldValues[$i];
                        $fieldValue->setFieldValue($newFieldValue);
                        $productFieldValues[] = $fieldValue;
                    } else {
                        // CREATE
                        $productField = $fieldRepo->find($i);
                        $fieldValue = new ProductFieldValue();
                        $fieldValue->setInventoryItem($product);
                        $fieldValue->setProductField($productField);
                        $fieldValue->setFieldValue($newFieldValue);
                        $productFieldValues[] = $fieldValue;
                    }
                }
            }
            $product->setFieldValues($productFieldValues);

            // Add inventory if it's a new item
            // @todo this is copied in item_copy route
            if (!$id) {

                /** @var \AppBundle\Entity\InventoryLocation $location */
                $locationRepo = $em->getRepository('AppBundle:InventoryLocation');

                $locationId = $form->get('inventoryLocation')->getData();
                $location = $locationRepo->find($locationId);

                $transactionRow = new ItemMovement();
                $transactionRow->setInventoryItem($product);
                $transactionRow->setInventoryLocation($location);
                $transactionRow->setCreatedBy($user);

                $em->persist($transactionRow);

                $product->setInventoryLocation($location);

                $note = new Note();
                $note->setCreatedBy($user);
                $note->setText('Added item to <strong>'.$location->getSite()->getName().' / '.$location->getName().'</strong>');
                $note->setInventoryItem($product);
                $em->persist($note);

                // Save the last item type used
                /** @var $repo \AppBundle\Repository\SettingRepository */
                $settingRepo =  $em->getRepository('AppBundle:Setting');
                if (!$setting = $settingRepo->findOneBy(['setupKey' => 'last_item_type'])) {
                    $setting = new Setting();
                    $setting->setSetupKey('last_item_type');
                }
                $setting->setSetupValue($form->get('itemType')->getData());
                $em->persist($setting);
            }

            // If no main image, use the first
            if (count($product->getImages()) > 0 && !$product->getImageName()) {
                $images = $product->getImages();
                $firstImageName = $images[0]->getImageName();
                $product->setImageName($firstImageName);
            }

            // If no images, unset main image
            if (count($product->getImages()) == 0 && $product->getImageName()) {
                $product->setImageName("");
            }

            // Translations
            // (empty fields post no values so we have to null them if we're trying to empty values)
            // @TODO causes fail in saving items since symfony upgrade
//            if ($product->getId()) {
//                $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
//                $translatableFields = ['name', 'description', 'componentInformation', 'careInformation'];
//                foreach ($translatableFields AS $fieldKey) {
//                    $fieldData = $form->get($fieldKey)->getData();
//                    if (is_array($fieldData)) {
//                        // This account has multiple languages defined
//                        foreach ($fieldData AS $lang => $value) {
//                            $repository->translate($product, $fieldKey, $lang, $value);
//                        }
//                    }
//                }
//            }

            $em->persist($product);

            // update the other items in the group
            $groupSimilarItems = $this->get('settings')->getSettingValue('group_similar_items');
            if (count($items) > 1 && $groupSimilarItems) {
                /** @var \AppBundle\Entity\InventoryItem $copyItem */
                foreach($items AS $copyItem) {

                    // The following are copied across to other items
                    if ($copyItem->getId() == $product->getId()) {
                        continue;
                    }

                    $copyItem->setName($product->getName());
                    $copyItem->setBrand($product->getBrand());
                    $copyItem->setImageName($product->getImageName()); // primary thumbnail
                    $copyItem->setMaxLoanDays($product->getMaxLoanDays());
                    $copyItem->setLoanFee($product->getLoanFee());
                    $copyItem->setKeywords($product->getKeywords());
                    $copyItem->setTags($product->getTags());
                    $copyItem->setShowOnWebsite($product->getShowOnWebsite());
                    $copyItem->setCareInformation($product->getCareInformation());
                    $copyItem->setDescription($product->getDescription());
                    $copyItem->setComponentInformation($product->getComponentInformation());
                    $copyItem->setPriceCost($product->getPriceCost());
                    $copyItem->setPriceSell($product->getPriceSell());
                    $copyItem->setDepositAmount($product->getDepositAmount());
                    $copyItem->setNote($product->getNote());
                    $copyItem->setIsReservable($product->getIsReservable());
                    $copyItem->setCheckInPrompts($product->getCheckInPrompts());
                    $copyItem->setCheckOutPrompts($product->getCheckOutPrompts());
                    $copyItem->setSites($product->getSites());

                    /** @var \AppBundle\Entity\Image $image */
                    foreach ($product->getImages() AS $image) {
                        $found = false;
                        foreach ($copyItem->getImages() AS $i) {
                            if ($i->getImageName() == $image->getImageName()) {
                                $found = true;
                            }
                        }
                        if ($found == false) {
                            $newImage = new Image();
                            $newImage->setInventoryItem($copyItem);
                            $newImage->setImageName($image->getImageName());
                            $copyItem->addImage($newImage);
                        }
                    }

                    /** @var \AppBundle\Entity\FileAttachment $file */
                    foreach ($product->getFileAttachments() AS $file) {
                        $found = false;
                        /** @var \AppBundle\Entity\FileAttachment $f */
                        foreach ($copyItem->getFileAttachments() AS $f) {
                            if ($f->getFileName() == $file->getFileName()) {
                                $found = true;
                            }
                        }
                        if ($found == false) {
                            $newFileAttachment = new FileAttachment();
                            $newFileAttachment->setInventoryItem($copyItem);
                            $newFileAttachment->setFileName($file->getFileName());
                            $newFileAttachment->setFileSize($file->getFileSize());
                            $newFileAttachment->setSendToMemberOnCheckout($file->getSendToMemberOnCheckout());
                            $copyItem->addFileAttachment($newFileAttachment);
                        }
                    }

                    /** @var \AppBundle\Entity\ProductFieldValue $fieldValue */
                    foreach ($product->getFieldValues() AS $fieldValue) {
                        $newFieldValue = clone($fieldValue);
                        $em->detach($newFieldValue);
                        $newFieldValue->setInventoryItem($copyItem);
                        $copyItem->addFieldValue($fieldValue);
                        $em->persist($newFieldValue);
                    }

                    $em->persist($copyItem);
                }
            }

            try {
                $em->flush();
                if ($request->get('submitForm') == 'saveAndNew') {
                    $this->addFlash('success', "Item saved. Ready to add a new one!");
                    return $this->redirectToRoute('item_type');
                } elseif ($request->get('submitForm') == 'saveAndCopy') {
                    return $this->redirectToRoute('item_copy', ['id' => $product->getId()]);
                } else {
                    $this->addFlash('success', "Item saved.");
                    return $this->redirectToRoute('item', ['id' => $product->getId()]);
                }
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'Item failed to save.');
                $this->addFlash('debug', $generalException->getMessage());
            }

        }

        if (count($customFields) > 0) {
            $customFieldsExist = true;
        } else {
            $customFieldsExist = false;
        }

        // Get active loan or reservation information
        $loanRowDetail = $this->getLoanRowDetail($product);

        return $this->render('item/item.html.twig', array(
            'form' => $form->createView(),
            'title' => $pageTitle,
            'countItems' => count($items),
            'customFieldsExist' => $customFieldsExist,
            'item' => $product,
            'isMultiSite' => $this->get('settings')->getSettingValue('multi_site'),
            'activeLoanInformation' => $loanRowDetail // for the stock info header
        ));
    }

    /**
     * THIS IS REPLICATED IN ItemCopyController
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

    /**
     * @param InventoryItem $product
     * @return array
     */
    private function getLoanRowDetail(InventoryItem $product)
    {

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var $reservationService \AppBundle\Services\Booking\BookingService */
        $reservationService = $this->get("service.booking");

        $loanRowDetail = [];

        if ($product->getInventoryLocation()->getId() == 1) {

            // get the information about any current loan
            $filter = [
                'item_ids' => [$product->getId()],
                'statuses' => ['ACTIVE', 'OVERDUE']
            ];
            $loansForItem = $inventoryService->getItemsOnLoan($filter);

            foreach ($loansForItem AS $loanRow) {
                /** @var $loanRow \AppBundle\Entity\LoanRow */
                if ($product->getId() == $loanRow->getInventoryItem()->getId()) {
                    $loanRowDetail = [
                        'loanId' => $loanRow->getLoan()->getId(),
                        'status' => $loanRow->getLoan()->getStatus(),
                        'contactName' => $loanRow->getLoan()->getContact()->getName(),
                        'dateFrom' => $loanRow->getDueOutAt(),
                        'dateTo' => $loanRow->getDueInAt()
                    ];
                }
            }

        } else {

            // see if it's reserved but not yet collected
            $filter = [
                'item_ids' => [$product->getId()],
                'current'  => true
            ];
            $reservationLoanRows = $reservationService->getBookings($filter);
            foreach ($reservationLoanRows AS $reservation) {
                /** @var $reservation \AppBundle\Entity\LoanRow */
                if ($product->getId() == $reservation->getInventoryItem()->getId()) {
                    $loanRowDetail = [
                        'loanId' => $reservation->getLoan()->getId(),
                        'status' => $reservation->getLoan()->getStatus(),
                        'contactName' => $reservation->getLoan()->getContact()->getName(),
                        'dateFrom' => $reservation->getDueOutAt(),
                        'dateTo' => $reservation->getDueInAt()
                    ];
                }
            }

        }

        return $loanRowDetail;

    }

}
