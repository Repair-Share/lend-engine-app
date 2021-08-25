<?php

namespace AppBundle\Controller\Admin\Item;

use AppBundle\Entity\FileAttachment;
use AppBundle\Entity\Image;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Note;
use AppBundle\Entity\ProductFieldValue;
use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $skuStub = $this->get('service.tenant')->getCodeStub();
        $itemSectorId = null;
        $inventory = null;

        if ($id) {

            // Editing item
            if (!$product = $itemRepo->find($id)) {
                $this->addFlash("error", "Item {$id} not found");
                return $this->redirectToRoute('homepage');
            }

            $pageTitle = $product->getName();

            /** @var \AppBundle\Entity\ItemSector $itemSector */
            if ($itemSector = $product->getItemSector()) {
                $itemSectorId = $itemSector->getId();
            }

            $inventory = $itemService->getInventory($product);

        } else {

            // Creating a new item
            if ($request->get('type') == InventoryItem::TYPE_KIT) {
                $pageTitle = 'Add a new kit';
            } else if ($request->get('type') == InventoryItem::TYPE_STOCK) {
                $pageTitle = 'Add a stock item';
            } else if ($request->get('type') == InventoryItem::TYPE_SERVICE) {
                $pageTitle = 'Add a new service';
            } else {
                $pageTitle = 'Add a new item';
            }

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

            if ($site->getDefaultCheckInLocation() === null) {
                $this->addFlash('error', "We don't have a default check in location for site ID {$site->getName()}, please set up.");
                return $this->redirectToRoute('site_list');
            }

            $defaultLocationId = $site->getDefaultCheckInLocation()->getId();

            /** @var \AppBundle\Repository\InventoryLocationRepository $locationRepo */
            $locationRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryLocation');
            $location = $locationRepo->find($defaultLocationId);
            $product->setInventoryLocation($location);

            /** @var \AppBundle\Repository\ItemSectorRepository $itemSectorRepo */
            $itemSectorRepo = $this->getDoctrine()->getRepository('AppBundle:ItemSector');
            $itemSectorId = $request->get('sectorId');
            if ($itemSectorId) {
                if (!$itemSector = $itemSectorRepo->find($itemSectorId)) {
                    $this->addFlash('error', "Item type {$itemSectorId} not found");
                    return $this->redirectToRoute('item');
                }
                $product->setItemSector($itemSector);
            }

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

            $product->setItemType(InventoryItem::TYPE_LOAN);
            if ($request->get('type') == InventoryItem::TYPE_KIT) {
                $product->setItemType(InventoryItem::TYPE_KIT);
            } else if ($request->get('type') == InventoryItem::TYPE_SERVICE) {
                $product->setItemType(InventoryItem::TYPE_SERVICE);
            } else if ($request->get('type') == InventoryItem::TYPE_STOCK) {
                $product->setItemType(InventoryItem::TYPE_STOCK);
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

        $formOptions = [
            'em' => $em,
            'donatedBy' => $request->get('item_donated_by'),
            'customFields' => $customFields,
            'customFieldValues' => $customFieldValues,
            'itemSectorId' => $itemSectorId // manually set as it's unmapped
        ];

        $form = $this->createForm(ItemType::class, $product, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $sku = $form->get('sku')->getData();
            if ($sku == '{auto}') {
                $newSku = $itemService->generateAutoSku($skuStub);
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

            // Add inventory of 1 if it's a new loan item
            // @todo this is copied in item_copy route
            if (!$id && $product->getItemType() == InventoryItem::TYPE_LOAN) {

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
                $note->setText('Added 1 x item to <strong>'.$location->getSite()->getName().' / '.$location->getName().'</strong>');
                $note->setInventoryItem($product);
                $em->persist($note);
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

            if ($quantities = $request->get('component_qty')) {
                foreach ($quantities AS $componentId => $quantity) {
                    /** @var \AppBundle\Entity\KitComponent $kitComponent */
                    foreach ($product->getComponents() AS $kitComponent) {
                        if ($kitComponent->getComponent()->getId() == $componentId) {
                            $kitComponent->setQuantity($quantity);
                        }
                    }
                }
            }

            if ($product->getItemType() == InventoryItem::TYPE_KIT && count($product->getComponents()) == 0) {
                $product->setShowOnWebsite(false);
            }

            try {
                $em->flush();
                if ($request->get('submitForm') == 'saveAndNew') {
                    $this->addFlash('success', "Item saved.");
                    return $this->redirectToRoute('item');
                } elseif ($request->get('numberOfCopies') > 0) {
                    return $this->redirectToRoute('item_copy', ['id' => $product->getId(), 'numberOfCopies' => $request->get('numberOfCopies')]);
                } else {
                    $this->addFlash('success', "Item saved.");
                    return $this->redirectToRoute('item', ['id' => $product->getId()]);
                }
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'Item failed to save.');
                $this->addFlash('debug', $generalException->getMessage());
            }

        }

        if ($product->getItemType() == InventoryItem::TYPE_KIT && count($product->getComponents()) == 0) {
            $this->addFlash('error', "This kit has no components yet. Members will not be able to see it online.");
        }

        if (count($customFields) > 0) {
            $customFieldsExist = true;
        } else {
            $customFieldsExist = false;
        }

        /** @var \AppBundle\Repository\MaintenancePlanRepository $maintenancePlanRepo */
        $maintenancePlanRepo = $em->getRepository('AppBundle:MaintenancePlan');
        $maintenancePlans = $maintenancePlanRepo->findAllOrderedByName();

        // Get active loan or reservation information
        $loanRowDetail = $this->getLoanRowDetail($product);

        return $this->render('admin/item/item.html.twig', array(
            'form' => $form->createView(),
            'title' => $pageTitle,
            'countItems' => count($items),
            'customFieldsExist' => $customFieldsExist,
            'maintenancePlans' => $maintenancePlans,
            'item' => $product,
            'inventory' => $inventory,
            'isMultiSite' => $this->get('settings')->getSettingValue('multi_site'),
            'activeLoanInformation' => $loanRowDetail // for the stock info header
        ));
    }

    /**
     * @param InventoryItem $product
     * @return array
     */
    private function getLoanRowDetail(InventoryItem $product)
    {

        /** @var \AppBundle\Services\Loan\LoanRowService $loanRowService */
        $loanRowService = $this->get('service.loan_row');

        /** @var $reservationService \AppBundle\Services\Booking\BookingService */
        $reservationService = $this->get("service.booking");

        $loanRowDetail = [];

        if ($product->getInventoryLocation() && $product->getInventoryLocation()->getId() == 1) {

            // get the information about any current loan
            $filter = [
                'item_ids' => [$product->getId()],
                'statuses' => ['ACTIVE', 'OVERDUE']
            ];
            $loansForItem = $loanRowService->getItemsOnLoan($filter);

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
