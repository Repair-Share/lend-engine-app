<?php

namespace AppBundle\Controller\Item;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemExportController extends Controller
{

    /**
     * @Route("admin/export/items/", name="export_items")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportItemAction(Request $request)
    {

        $container = $this->container;
        $response = new StreamedResponse(function() use($container) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Added on',
                'Code',
                'Name',
                'Type',
                'Serial number',
                'Condition',
                'Tags',
                'Location',
                'Brand',
                'Price paid',
                'Value (RRP)',
                'Shown on website',
                'Loan fee',
                'Loan period',
                'Short description',
                'Long description',
                'Components',
                'Care information',
                'Keywords',
            ];

            /** @var \AppBundle\Repository\ProductFieldRepository $fieldRepo */
            $fieldRepo = $this->getDoctrine()->getRepository('AppBundle:ProductField');

            /** @var \AppBundle\Repository\ProductFieldSelectOptionRepository $fieldOptionRepo */
            $fieldOptionRepo = $this->getDoctrine()->getRepository('AppBundle:ProductFieldSelectOption');

            $customFields = $fieldRepo->findAllOrderedBySort();

            foreach ($customFields AS $field) {
                /** @var $field \AppBundle\Entity\ProductField */
                $header[] = $field->getName();
            }

            fputcsv($handle, $header);

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
            $itemRepo = $em->getRepository('AppBundle:InventoryItem');
            $items = $itemRepo->findBy(['isActive' => true]);

            foreach ($items AS $item) {
                /** @var $item \AppBundle\Entity\InventoryItem */

                $tagNameArray = [];
                $tags = $item->getTags();
                foreach ($tags AS $tag) {
                    $tagNameArray[] = $tag->getName();
                }

                $condition = '';
                if ($item->getCondition()) {
                    $condition = $item->getCondition()->getName();
                }

                $showOnWebsite = '';
                if ($item->getShowOnWebsite() == 1) {
                    $showOnWebsite = 'Yes';
                }

                $itemTypeName = '';
                if ($item->getItemType()) {
                    $itemTypeName = $item->getItemType()->getName();
                }

                $itemArray = [
                    $item->getCreatedAt()->format("Y-m-d"),
                    $item->getSku(),
                    $item->getName(),
                    $itemTypeName,
                    $item->getSerial(),
                    $condition,
                    implode(',', $tagNameArray),
                    $item->getInventoryLocation()->getName(),
                    $item->getBrand(),
                    $item->getPriceCost(),
                    $item->getPriceSell(),
                    $showOnWebsite,
                    $item->getLoanFee(),
                    $item->getMaxLoanDays(),
                    $item->getNote(),
                    $item->getDescription(),
                    $item->getComponentInformation(),
                    $item->getCareInformation(),
                    $item->getKeywords(),
                ];

                $customFieldValues = $item->getFieldValues();

                foreach ($customFields AS $field) {
                    /** @var $field \AppBundle\Entity\ProductField */
                    $fieldId   = $field->getId();

                    $value = '';
                    if (isset($customFieldValues[$fieldId])) {
                        /** @var \AppBundle\Entity\ProductFieldValue $productFieldValue */
                        $productFieldValue = $customFieldValues[$fieldId];
                        if ($field->getType() == 'choice' && $optionId = $productFieldValue->getFieldValue()) {
                            if ($fieldOptionRepo->find($optionId)) {
                                $value = $fieldOptionRepo->find($optionId)->getOptionName();
                            }
                        } else if ($field->getType() == 'multiselect' && $optionIdString = $productFieldValue->getFieldValue()) {
                            $optionIds = explode(',', $optionIdString);
                            $itemFieldSelectOptionNames = [];
                            foreach ($optionIds AS $optionId) {
                                if ($fieldOptionRepo->find($optionId)) {
                                    $itemFieldSelectOptionNames[] = $fieldOptionRepo->find($optionId)->getOptionName();
                                }
                            }
                            $value = implode(',', $itemFieldSelectOptionNames);
                        } else if ($field->getType() == 'checkbox') {
                            if ($productFieldValue->getFieldValue() == 1) {
                                $value = 'Yes';
                            }
                        } else {
                            $value = $productFieldValue->getFieldValue();
                        }
                    }

                    $itemArray[] = $value;
                }

                fputcsv($handle, $itemArray);

            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="items.csv"');

        return $response;

    }

}