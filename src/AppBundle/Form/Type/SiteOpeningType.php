<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteOpeningType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('timeFrom', TextType::class, array(
            'label' => 'From',
            'attr' => [
                'placeholder' => 'eg 0900',
                'onblur'      => 'handleTimeChange(this)'
            ]
        ));

        $builder->add('timeChangeover', TextType::class, array(
            'label' => 'Changeover',
            'required' => false,
            'attr' => [
                'placeholder' => 'eg 1100',
                'onblur'      => 'handleTimeChange(this)'
            ]
        ));

        $builder->add('timeTo', TextType::class, array(
            'label' => 'To',
            'attr' => [
                'placeholder' => 'eg 1700',
                'onblur'      => 'handleTimeChange(this)'
            ]
        ));

        $choices = [
            'Monday'    => '1',
            'Tuesday'   => '2',
            'Wednesday' => '3',
            'Thursday'  => '4',
            'Friday'    => '5',
            'Saturday'  => '6',
            'Sunday'    => '7'
        ];
        $builder->add('weekDay', ChoiceType::class, array(
            'choices' => $choices,
            'label' => 'Week day',
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\SiteOpening',
        ));
    }
}