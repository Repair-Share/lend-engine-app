<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OpeningHoursType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $builder->getData();

        $builder->add('site', EntityType::class, array(
            'label' => 'Site',
            'class' => 'AppBundle:Site',
            'choice_label' => 'name',
            'required' => true,
            'attr' => array(
                'data-help' => "",
            )
        ));

        if ($data->getDate()) {
            $date = $data->getDate()->format("Y-m-d");
        } else {
            $d = new \DateTime();
            $date = $d->format("Y-m-d");
        }
        $builder->add('date', HiddenType::class, array(
            'label' => 'Date',
            'data' => $date,
            'required' => true
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
            'Open' => 'o',
            'Closed' => 'c'
        ];
        $builder->add('type', ChoiceType::class, array(
            'label' => 'Open or closed?',
            'choices' => $choices,
            'attr' => array(
                'placeholder' => 'yyyy-mm-dd'
            )
        ));

        $builder->add('repeat', ChoiceType::class, array(
            'label' => 'Repeat?',

            'choices' => [
                'No repeat'  => '1',
                'Repeat yearly for 2 years' => '2',
                'Repeat yearly for 3 years' => '3',
                'Repeat yearly for 4 years' => '4',
                'Repeat yearly for 5 years' => '5'
            ],

            'attr' => array(
                'data-help' => 'Repeat the closing status for the same day(s) in the next years'
            )
        ));

    }

}