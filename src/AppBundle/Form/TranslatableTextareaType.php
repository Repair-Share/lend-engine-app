<?php

namespace AppBundle\Form;

use AppBundle\Interfaces\TranslatableFieldInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslatableTextareaType extends AbstractType
    implements TranslatableFieldInterface{

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            "compound"        => true,
        ]);
        $resolver->setRequired(["compound"]);
        $resolver->setAllowedValues("compound", true);
    }

    public function getParent()
    {
        return TextareaType::class;
    }
}