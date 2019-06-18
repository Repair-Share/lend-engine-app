<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EventPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('paymentMethod', EntityType::class, array(
            'label' => 'Payment method',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'required' => false,
            'attr' => [
                'class' => 'payment-method'
            ]
        ));

        $builder->add('paymentAmount', CurrencyamountType::class, array(
            'label' => 'Amount',
            'required' => true,
            'attr' => [
                'class' => 'payment-amount'
            ]
        ));

        $builder->add('paymentNote', TextareaType::class, array(
            'label' => 'Payment note',
            'required' => false,
            'attr' => array(
                'rows' => 2
            )
        ));

        $builder->add('attendeeId', HiddenType::class, array(
            'label' => 'attendeeId',
            'required' => false,
            'mapped' => false,
            'attr' => []
        ));

        // Set by JS on the return from Stripe.com checkout
        $builder->add('stripeToken', HiddenType::class, array(
            'label' => 'stripeToken',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'stripe-token'
            ]
        ));

        $builder->add('stripeCardId', HiddenType::class, array(
            'label' => 'stripeCardId',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'stripe-card-id'
            ]
        ));

    }
}