<?php
// src/AppBundle/Form/Type/InventoryLocationType.php
namespace AppBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class ItemLocationType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array(
            'label' => 'Choose a name for the location',
            'required' => true,
            'attr' => array(
                'placeholder' => 'e.g. "Store room"',
                'data-help' => '',
            )
        ));

        $builder->add('site', EntityType::class, array(
            'label' => 'Which site is this location in?',
            'class' => 'AppBundle:Site',
            'choice_label' => 'name',
            'required' => true,
            'attr' => array(
                'data-help' => "",
            )
        ));

        $builder->add('isAvailable', CheckboxType::class, array(
            'label' => 'Items in this location are available to loan',
            'required' => false
        ));

        $builder->add('isActive', CheckboxType::class, array(
            'label' => 'Active?',
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => "If you've used a location you can't delete it; you'd need to deactivate it.",
            )
        ));

    }

}