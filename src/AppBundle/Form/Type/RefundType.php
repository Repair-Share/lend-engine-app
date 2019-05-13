<?php
// src/AppBundle/Form/Type/RefundType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

class RefundType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('paymentMethod', EntityType::class, array(
            'label' => 'Refund with',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'required' => true,
            'mapped' => true,
            'attr' => [
                'class' => 'payment-method'
            ]
        ));

        $builder->add('amount', CurrencyamountType::class, array(
            'label' => 'Amount',
            'required' => false,
            'attr' => [
                'class' => 'input-100'
            ]
        ));

        $builder->add('note', TextareaType::class, array(
            'label' => 'Optional note',
            'required' => false,
            'attr' => [
                'rows' => 2
            ]
        ));

        $builder->add('paymentId', HiddenType::class, array(
            'label' => 'paymentId',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => ''
            ]
        ));

    }

}