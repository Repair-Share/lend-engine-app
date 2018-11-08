<?php
// src/AppBundle/Form/Type/PaymentType.php
namespace AppBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('paymentMethod', EntityType::class, array(
            'label' => 'Payment method',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'required' => true,
            'mapped' => true,
            'attr' => [
                'class' => 'payment-method'
            ]
        ));

        $builder->add('paymentDate', TextType::class, array(
            'label' => 'Date',
            'required' => false,
            'attr' => [
                'class' => 'single-date-picker'
            ]
        ));

        $builder->add('amount', TextType::class, array(
            'label' => 'Amount',
            'required' => false,
            'attr' => [
                'class' => 'payment-amount'
            ]
        ));

        $builder->add('note', TextareaType::class, array(
            'label' => 'Payment note',
            'required' => false,
            'attr' => array(
                'rows' => 2
            )
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Payment',
        ));
    }

    /**
     * Required function for form types
     * @return string
     */
    public function getName()
    {
        return "payment_type";
    }
}