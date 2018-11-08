<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\Site;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SiteDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     * @return Site
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $site = new Site();
        $site->setId($object['id']);
        $site->setName($object['name']);

        return $site;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {
        if ($type != Site::class) {
            return false;
        }
        return true;
    }

}