<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenancePlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'required' => true
        ]);

        $builder->add('interval', TextType::class, [
            'label' => 'Monthly interval',
            'required' => true
        ]);

        $builder->add('description', TextareaType::class, array(
            'label' => 'Description',
            'attr' => [
                'rows' => 8,
                'data-help' => "Shows as a guide for maintainers. Max 1000 characters."
            ]
        ));

        $builder->add('isActive', CheckboxType::class, array(
            'label' => '',
            'attr' => array(
                'data-help' => '',
            )
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\MaintenancePlan',
        ));
    }
}