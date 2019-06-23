<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\InventoryItem;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class InventoryItemDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     * @return InventoryItem
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $item = new InventoryItem();
        $item->setId($object['id']);
        $item->setName($object['name']);
        $item->setImageName($object['imageName']);

        if (!isset($object['depositAmount'])) {
            $object['depositAmount'] = 0;
        }
        $item->setDepositAmount($object['depositAmount']);

        if (!isset($object['serial'])) {
            $object['serial'] = null;
        }
        $item->setSerial($object['serial']);

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {
        if ($type != InventoryItem::class) {
            return false;
        }
        return true;
    }

}