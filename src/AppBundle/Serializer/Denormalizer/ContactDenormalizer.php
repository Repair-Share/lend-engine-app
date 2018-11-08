<?php

namespace AppBundle\Serializer\Denormalizer;

use AppBundle\Entity\Contact;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ContactDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     * @return Contact
     */
    public function denormalize($object, $class, $format = null, array $context = array())
    {
        $contact = new Contact();
        $contact->setId($object['id']);
        $contact->setFirstName($object['firstName']);
        $contact->setLastName($object['lastName']);

        return $contact;
    }

    /**
     * @inheritdoc
     */
    public function supportsDenormalization($object, $type, $format = null)
    {
        if ($type != Contact::class) {
            return false;
        }
        return true;
    }

}