<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OpeningTimeExceptionType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('site', EntityType::class, array(
            'label' => 'Site',
            'class' => 'AppBundle:Site',
            'choice_label' => 'name',
            'required' => true,
            'attr' => array(
                'data-help' => "",
            )
        ));

        $builder->add('date', HiddenType::class, array(
            'label' => 'Date',
            'required' => true,
            'attr' => array(
                'placeholder' => 'yyyy-mm-dd'
            )
        ));

        $builder->add('timeFrom', TextType::class, array(
            'label' => 'Time from',
            'required' => true,
            'attr' => array(
                'data-help' => 'eg 0900',
                'maxlength' => 4
            )
        ));

        $builder->add('timeChangeover', TextType::class, array(
            'label' => 'Changeover',
            'required' => false,
            'attr' => array(
                'data-help' => 'eg 1115',
                'maxlength' => 4
            )
        ));

        $builder->add('timeTo', TextType::class, array(
            'label' => 'Time to',
            'required' => true,
            'attr' => array(
                'data-help' => 'eg 1400 (24 hours)',
                'maxlength' => 4
            )
        ));

        $choices = [
            'Closed' => 'c',
            'Open' => 'o'
        ];
        $builder->add('type', ChoiceType::class, array(
            'label' => 'Open or closed?',
            'choices' => $choices,
            'attr' => array(
                'placeholder' => 'yyyy-mm-dd'
            )
        ));

    }

}