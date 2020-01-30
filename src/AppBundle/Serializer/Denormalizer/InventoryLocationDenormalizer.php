<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\InventoryLocation;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class InventoryLocationDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     * @return InventoryLocation
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $location = new InventoryLocation();
        $location->setId($object['id']);
        $location->setName($object['name']);
        $location->setSite($object['site']);
        return $location;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {
        if ($type != InventoryLocation::class) {
            return false;
        }
        return true;
    }

}