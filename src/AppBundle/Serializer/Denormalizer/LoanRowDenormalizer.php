<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Site;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class LoanRowDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     * @return LoanRow
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $loanRow = new LoanRow();

        $loanRow->setFee($object['fee']);

        $dFrom = new \DateTime($object['dueOutAt']);
        $dTo   = new \DateTime($object['dueInAt']);

        $loanRow->setDueOutAt($dFrom);
        $loanRow->setDueInAt($dTo);
        $loanRow->setDuration($object['duration']);

        if (!isset($object['productQuantity'])) {
            $object['productQuantity'] = 1;
        }
        $loanRow->setProductQuantity($object['productQuantity']);

        $siteDenormalizer = new SiteDenormalizer();

        // Site to is not provided for the service items
        if (isset($object['siteFrom'])) {

            /** @var Site $site */
            $site = $siteDenormalizer->denormalize(
                $object['siteFrom'],
                Site::class,
                $format,
                $context
            );
            $loanRow->setSiteFrom($site);

        }

        // Site to is not provided for the stock items and for the service items
        if (isset($object['siteTo'])) {

            $site = $siteDenormalizer->denormalize(
                $object['siteTo'],
                Site::class,
                $format,
                $context
            );
            $loanRow->setSiteTo($site);

        }

        if (isset($object['inventoryItem'])) {
            $inventoryItemDenormalizer = new InventoryItemDenormalizer();
            /** @var InventoryItem $item */
            $item = $inventoryItemDenormalizer->denormalize(
                $object['inventoryItem'],
                InventoryItem::class,
                $format,
                $context
            );
            $loanRow->setInventoryItem($item);
        }

        // Required for stock items
        if (isset($object['itemLocation'])) {
            $inventoryLocationDenormalizer = new InventoryLocationDenormalizer();
            /** @var InventoryLocation $location */
            $location = $inventoryLocationDenormalizer->denormalize(
                $object['itemLocation'],
                InventoryLocation::class,
                $format,
                $context
            );
            $loanRow->setItemLocation($location);
        }

        return $loanRow;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {
        if ($type != LoanRow::class) {
            return false;
        }
        return true;
    }

}