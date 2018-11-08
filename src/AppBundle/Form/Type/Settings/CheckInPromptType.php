<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CheckInPromptType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array(
            'label' => 'Name',
            'required' => true
        ));

        $builder->add('defaultOn', CheckboxType::class, array(
            'label' => 'Automatically select this for new items'
        ));

        $builder->add('setForAllItems', CheckboxType::class, array(
            'label' => 'Assign this to all existing items',
            'mapped' => false
        ));
    }

}