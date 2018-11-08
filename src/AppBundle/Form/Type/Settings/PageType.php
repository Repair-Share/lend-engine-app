<?php
// src/AppBundle/Form/Type/InventoryLocationType.php
namespace AppBundle\Form\Type\Settings;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PageType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array(
            'label' => 'Name (also used as menu text on the website)',
            'required' => true,
            'attr' => array(
                'placeholder' => 'e.g. "Privacy"',
                'data-help' => '',
            )
        ));

        $visChoices = array(
            'Hidden' => 'HIDDEN',
            'Staff only' => 'ADMIN',
            'Members only' => 'MEMBERS',
            'Public' => 'PUBLIC'
        );
        $builder->add('visibility', ChoiceType::class, array(
            'label' => 'Visibility',
            'required' => true,
            'choices' => $visChoices,
            'attr' => array(

            )
        ));

        $builder->add('title', TextType::class, array(
            'label' => 'Title (shown on the page)',
            'required' => false,
            'attr' => array(
                'placeholder' => 'e.g. "Our Privacy Policy"',
                'data-help' => '',
            )
        ));

        $builder->add('url', TextType::class, array(
            'label' => 'Custom link',
            'required' => false,
            'attr' => array(
                'placeholder' => 'e.g. "http://www.mysite.com/terms"',
                'data-help' => '',
            )
        ));

        $builder->add('content', TextareaType::class, array(
            'label' => 'Page content',
            'required' => false,
            'attr' => array(
                'rows' => 10,
                'class' => 'limited',
                'placeholder' => 'HTML is supported.',
                'data-help' => 'Use "&lt;br /&gt;" as a line break. Maximum 65,000 characters.'
            )
        ));

    }

}