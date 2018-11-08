<?php

namespace AppBundle\Controller\Import;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemCondition;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\ProductTag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ItemImportController extends Controller
{

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @Route("admin/import/items/", name="import_items")
     */
    public function importItemAction(Request $request)
    {

        $user = $this->getUser();
        $formBuilder = $this->createFormBuilder();
        $em = $this->getDoctrine()->getManager();

        // Default location
        /** @var \AppBundle\Repository\InventoryLocationRepository $repo */
        $repo = $em->getRepository('AppBundle:InventoryLocation');
        $defaultLocation = $repo->find(2);

        $header = [];

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        $formBuilder->add('csv_data', TextareaType::class, array(
            'label' => 'Paste tab separated data, one item per line.',
            'attr' => array(
                'rows' => 20,
                'placeholder' => "Include a header row using any of the columns shown on the right. Code is a mandatory column.",
                'data-help' => ''
            )
        ));

        $formBuilder->add('addItems', CheckboxType::class, array(
            'label' => 'Create new items where code is not found',
            'required' => false,
            'attr' => array(
                'data-help' => ''
            )
        ));

        $formBuilder->add('save', SubmitType::class, array(
            'label' => 'Update / import items',
            'attr' => array(
                'class' => 'btn-success'
            )
        ));

        $form = $formBuilder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Read the CSV
            $csv_data = $form->get('csv_data')->getData();
            $rows = explode("\n",$csv_data);

            // Get a mapping of column names to index, eg "Code" = 2
            $columnMap = $this->mapColumnsToData($rows);

            $created = 0;
            $ignored = 0;
            $updated = 0;

            foreach ($rows AS $rowId => $row) {

                // First time through, capture the header (previously validated in mapColumnsToData)
                if ($rowId == 0) {
                    $header = str_getcsv($row, "\t");
                    continue; // skip header
                }

                // Get the item
                $item = str_getcsv($row, "\t");

                if (!isset($columnMap['Code']) || !$code = trim($item[$columnMap['Code']])) {
                    $this->addFlash('error', "Code on line {$rowId} was missing.");
                    continue;
                }

                // Pad out the row data if any columns were empty, and validate cells
                $rowOk = true;
                foreach ($header AS $i => $key) {
                    if (!isset($item[$i])) {
                        $item[$i] = '';
                    }

                    // Validate the cell, returning data type ready for the setter (eg tag ID)
                    $cellType = $this->getCellType($key);
                    $originalData = $item[$i];
                    $cleanedData = $this->validateCell($originalData, $cellType);
                    if (is_array($cleanedData)) {
                        $this->addFlash('error', "Line {$rowId} (code: '{$code}') contains bad data. '{$originalData}' for $key is not {$cellType}");
                        $rowOk = false;
                    } else {
                        $item[$i] = $cleanedData;
                    }
                }

                if ($rowOk == false) {
                    continue;
                }

                // Get the product
                $product = $itemRepo->findOneBy(['sku' => $code]);

                $action = 'create';
                if ($product) {
                    $updated++;
                    $action = 'update';
                } else if ($form->has('addItems') && $form->get('addItems')->getData()) {
                    if ($this->validateNewItem($item)) {
                        $product = new InventoryItem();
                        $product->setCreatedAt(new \DateTime());
                        $product->setSku($code);
                        $created++;
                    } else {
                        $this->addFlash('report', "Skipping row {$rowId} with code {$code} : Not enough data to create a new item.");
                        continue;
                    }
                } else {
                    // Item not found, don't update anything
                    $this->addFlash('report', "Skipping item on row {$rowId} : item with code '{$code}' was not found.");
                    $ignored++;
                    continue;
                }

                // Clean the data
                $setters = $this->getHeaderKeys();
                foreach ($columnMap AS $colName => $colKey) {
                    if (isset($item[$colKey]) && $colName != "Code") {
                        $d = $item[$colKey];
//                        $this->addFlash('report', "Setting {$colName} to '{$d}' for item '{$code}'");
                        $colSetter = $setters[$colName];
                        $product->$colSetter($d);
                    }
                }

//                $location = null;
//                if ($locationName) {
//                    $location = $this->getOrCreateLocation($locationName);
//                }
//
//                if ($condition = $this->getConditionId($conditionName)) {
//                    $product->setCondition($condition);
//                }
//
//                $tags = explode(",", $tagString);
//                foreach ($tags as $tagName) {
//                    $tagName = strtoupper(trim($tagName));
//                    if ($tag = $this->getTagId($tagName)) {
//                        $product->addTag($tag);
//                    }
//                }

                $product->setUpdatedAt(new \DateTime());

                $em->persist($product);

                if ($action == 'update') {
//                    if ($product->getInventoryLocation()->getId() == 1) {
//                        $this->errors[] = "Could not move {$product->getName()} as it's on loan. ";
//                        $this->abortImportWithErrors();
//                    }
                } else {
                    // Creating a new item
                    $transactionRow = new ItemMovement();
                    $transactionRow->setInventoryItem($product);
                    $transactionRow->setInventoryLocation($defaultLocation);
                    $transactionRow->setCreatedBy($user);
                    $em->persist($transactionRow);
                    $product->setInventoryLocation($defaultLocation);
                }
            }

            // We could flush at the end, creating all items in one go, but as we are creating
            // categories etc on the fly, we need to persist for each product in the CSV
            try {
                $em->flush();
                if ($updated > 0) {
                    $this->addFlash('report', $updated.' items updated.');
                }
                if ($created > 0) {
                    $this->addFlash('report', $created.' items created.');
                }
                if ($ignored > 0) {
                    $this->addFlash('report', $ignored.' items skipped.');
                }
            } catch (\Exception $generalException) {
                $this->addFlash('debug', 'Failed to save: '.$generalException->getMessage());
            }

            return $this->redirectToRoute('import_items');
        }

        return $this->render('import/import_product.html.twig', [
            'form' => $form->createView(),
            'headerKeys' => $this->getHeaderKeys()
        ]);
    }

    /**
     * Parse the file into $header and $itemRows arrays
     * @param $rows array
     * @return array
     */
    private function mapColumnsToData($rows)
    {
        $header   = [];
        $map      = [];
        $rowCount = 0;

        foreach ($rows AS $k => $row) {
            if ($rowCount == 0) {
                $header = str_getcsv($row, "\t");
            } else {
                $itemRows[] = str_getcsv($row, "\t");
            }
            $rowCount++;
        }

        $this->validateHeader($header);

        // Return a mapping of column keys to index
        foreach ($header AS $i => $key) {
            $map[$key] = $i;
        }

        return $map;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function abortImportWithErrors()
    {
        foreach ($this->errors AS $error) {
            $this->addFlash('error', 'Failed : '.$error);
        }
        return $this->redirectToRoute('import_items');
    }

    /**
     * @param $products
     * @return bool
     */
    private function validateImportedProducts($products)
    {
        if (count($this->errors) > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $name
     * @return \AppBundle\Entity\ItemCondition
     */
    private function getConditionId($name)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ItemConditionRepository $repo */
        $repo = $em->getRepository('AppBundle:ItemCondition');

        if ($condition = $repo->findOneBy(['name' => $name])) {
            return $condition;
        } else {
            $condition = new ItemCondition();
            $condition->setName($name);
            $em->persist($condition);
            try {
                $em->flush();
                return $condition;
            } catch (\Exception $generalException) {
                $this->addFlash('error', $generalException->getMessage());
                return false;
            }
        }
    }

    /**
     * @param $name
     * @return object
     */
    private function getOrCreateTag($name)
    {
        static $tagNameArray = [];
        if (isset($tagNameArray[$name])) {
            return $tagNameArray[$name];
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ProductTagRepository $repo */
        $repo = $em->getRepository('AppBundle:ProductTag');

        if ($tag = $repo->findOneBy(['name' => $name])) {
            $tagNameArray[$name] = $tag;
            return $tag;
        } else {
            $tag = new ProductTag();
            $tag->setName($name);
            $em->persist($tag);
            try {
                $em->flush();
                $tagNameArray[$name] = $tag;
                return $tag;
            } catch (\Exception $generalException) {
                $this->addFlash('error', $generalException->getMessage());
                return false;
            }
        }
    }

    /**
     * @param $name
     * @return object
     */
    private function getOrCreateLocation($name)
    {
        static $locationNameArray = [];
        if (isset($locationNameArray[$name])) {
            return $locationNameArray[$name];
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryLocationRepository $repo */
        $repo = $em->getRepository('AppBundle:InventoryLocation');

        if ($location = $repo->findOneBy(['name' => $name])) {
            $locationNameArray[$name] = $location;
            return $location;
        } else {
            $location = new InventoryLocation();
            $location->setName($name);
            $location->setIsActive(true);
            $em->persist($location);
            try {
                $em->flush();
                $locationNameArray[$name] = $location;
                return $location;
            } catch (\Exception $generalException) {
                $this->addFlash('error', $generalException->getMessage());
                return false;
            }
        }
    }

    /**
     * @param $header
     * @return bool
     */
    private function validateHeader($header)
    {
        $validHeaderKeys = $this->getHeaderKeys();

        if (count($header) < 2) {
            $cols = count($header);
            $this->errors[] = "We only found {$cols} columns in your import. Please check it's tab delimited text.";
        }

        $foundCodeColumn = false;
        $errors = 0;
        foreach ($header AS $key) {
            if ($key == "Code") {
                $foundCodeColumn = true;
            }
            if (!in_array($key, array_keys($validHeaderKeys))) {
                $this->errors[] = "'{$key}' is not a valid column.";
                $errors++;
            }
        }

        if ($foundCodeColumn == false) {
            $this->errors[] = "Your import file needs a column called 'Code'.";
        }

        if ($errors > 0) {
            return $this->abortImportWithErrors();
        }

        return true;
    }

    /**
     * Check that this row has enough data to create a new item
     * @param $item array
     * @return bool
     */
    private function validateNewItem($item)
    {
        return true;
    }

    private function validateCell($data, $type)
    {
        switch ($type) {
            case 'text':
                if ($data) {
                    return $data;
                }
                break;
            case 'number':
                if (!is_numeric($data)) {
                    return array(
                        'error' => "Cell validation error : {$data} is not {$type}"
                    );
                } else {
                    return (float)$data;
                }
                break;
            case 'integer':
                if (!is_numeric($data)) {
                    return array(
                        'error' => "Cell validation error : {$data} is not {$type}"
                    );
                } else {
                    return (int)$data;
                }
                break;
            case 'boolean':
                if (strtolower($data) == 'yes') {
                    return 1;
                } else if (strtolower($data) == 'no') {
                    return 0;
                } else {
                    return array(
                        'error' => "Cell validation error : {$data} is not {$type}"
                    );
                }
                break;
            case 'category':
                    if ($tag = $this->getOrCreateTag($data)) {
                        return $tag;
                    } else {
                        return null;
                    }
                break;
        }

        return null;
    }

    private function getCellType($k)
    {
        $validations = [
            'Code' => 'text',
            'Name' => 'text',
            'Short description' => 'text',
            'Long description' => 'text',
            'Components' => 'text',
            'Care information' => 'text',
            'Serial' => 'text',
            'Brand' => 'text',
            'Price paid' => 'number',
            'Value' => 'number',
            'Loan fee' => 'number',
            'Loan period' => 'integer',
            'Keywords' => 'text',
            'Reservable' => 'boolean',
            'Category' => 'category'
        ];

        return $validations[$k];
    }

    /**
     * @return array
     */
    private function getHeaderKeys()
    {
        $validHeaderKeys = [
            'Code' => 'setSku',
            'Name' => 'setName',
//            'Short description' => 'setNote',
//            'Long description' => 'setDescription',
//            'Components' => 'setComponentInformation',
//            'Care information' => 'setCareInformation',
            'Serial' => 'setSerial',
//            'Condition' => 'setCondition',
            'Category' => 'addTag',
            'Brand' => 'setBrand',
            'Price paid' => 'setPriceCost',
            'Value' => 'setPriceSell',
            'Loan fee' => 'setLoanFee',
            'Loan period' => 'setMaxLoanDays',
            'Keywords' => 'setKeywords',
            'Reservable' => 'setIsReservable'
        ];

        return $validHeaderKeys;
    }

}