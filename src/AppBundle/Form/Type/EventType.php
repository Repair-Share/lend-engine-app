<?php
namespace AppBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventType extends AbstractType
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
                'data-help' => "If the event isn't at one of your existing sites, add a new site in the settings area first. It doesn't have to be active for item bookings.",
            )
        ));

        $choices = [
            'Yes' => 'o',
            'No'  => 'e'
        ];
        $builder->add('type', ChoiceType::class, array(
            'label' => 'Can people pick up and return items at this event?',
            'required' => true,
            'choices' => $choices,
            'attr' => [
                'data-help' => 'Choose yes to have this event show on the item booking calendar, along with regular site opening hours.',
                'class' => 'input-100'
            ]
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
            'required' => true,
            'attr' => array(
                'placeholder' => 'yyyy-mm-dd'
            )
        ));

        $builder->add('timeFrom', TextType::class, array(
            'label' => 'From',
            'required' => true,
            'attr' => [
                'maxlength' => 4,
                'class' => ''
            ]
        ));

        $builder->add('timeTo', TextType::class, array(
            'label' => 'To',
            'required' => true,
            'attr' => [
                'maxlength' => 4,
                'class' => ''
            ]
        ));

        $builder->add('isBookable', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Allow online booking?',
            'attr' => [
                'class' => 'input-100',
            ]
        ));

        $builder->add('price', NumberType::class, array(
            'label' => 'Price',
            'required' => false,
            'attr' => [
                'maxlength' => 5,
                'class' => 'input-100'
            ]
        ));

        $builder->add('title', TextType::class, array(
            'label' => 'Title',
            'required' => true,
            'attr' => array(
                'data-help' => ''
            )
        ));

        $builder->add('facebookUrl', TextType::class, array(
            'label' => 'Facebook event URL',
            'required' => false,
            'attr' => array(
                'data-help' => 'To provide a link for more info.'
            )
        ));

        $builder->add('description', TextareaType::class, array(
            'label' => 'Details',
            'required' => false,
            'attr' => [
                'rows' => 10,
                'placeholder' => "A short note describing the event."
            ]
        ));

        $builder->add('maxAttendees', NumberType::class, array(
            'label' => 'Maximum attendees',
            'required' => false,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "Set to zero for no limit."
            ]
        ));

    }

}