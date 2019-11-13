<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
            )
        ]);

        $builder->add('showOnWebsite', CheckboxType::class, array(
            'label' => 'Show this category on your Lend Engine site as a menu item.',
            'attr' => array(
                'data-help' => '',
            )
        ));

        $builder->add('section', EntityType::class, array(
            'label' => 'Section (category group)',
            'class' => 'AppBundle:ProductSection',
            'choice_label' => 'name',
            'required' => false,
            'attr' => [
                'data-help' => '<a href="/admin/section" class="modal-link">Add a new section</a>',
            ]
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ProductTag',
        ));
    }
}