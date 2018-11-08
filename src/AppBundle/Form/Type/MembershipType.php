<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MembershipType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('membershipType', EntityType::class, array(
            'class' => 'AppBundle:MembershipType',
            'choice_label' => 'fullName',
            'empty_data'  => '- Select -',
            'label' => 'Choose a membership type',
            'required' => true,
        ));

        $builder->add('price', TextType::class, array(
            'label' => 'Membership cost',
            'required' => true,
        ));

        $builder->add('paymentMethod', EntityType::class, array(
            'label' => 'Payment method',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'payment-method'
            ]
        ));

        $builder->add('paymentAmount', TextType::class, array(
            'label' => 'Amount',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'payment-amount'
            ]
        ));

        $builder->add('paymentNote', TextareaType::class, array(
            'label' => 'Payment note',
            'required' => false,
            'mapped' => false,
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
            'data_class' => 'AppBundle\Entity\Membership'
        ));
    }

    /**
     * Required function for form types
     * @return string
     */
    public function getName()
    {
        return "membership_type";
    }
}