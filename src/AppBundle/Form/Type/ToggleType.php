<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ToggleType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => [
                'Yes' => '1',
                'No'  => '0',
            ],
            'choices_as_values' => true,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}