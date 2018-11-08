<?php
// src/AppBundle/Form/Type/PaymentMethodType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PaymentMethodType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $paymentMethod = $builder->getData();

        $builder->add('name', TextType::class, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        if ($paymentMethodId = $paymentMethod->getId()) {
            $builder->add('isActive', CheckboxType::class, array(
                'label' => 'This payment method is active',
                'required' => false,
                'attr' => array(
                    'placeholder' => '',
                    'data-help' => "If you've used a payment method already, you can't delete it. You'd need to deactivate it.",
                )
            ));
        }
    }

    /**
     * Required function for form types
     * @return string
     */
    public function getName()
    {
        return "payment_method_type";
    }
}