<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyamountType extends AbstractType
{
    public function getParent()
    {
        return TextType::class;
    }
}