<?php
// src/AppBundle/Form/Type/RefundType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RefundType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];

        $activePaymentMethods = $this->em->getRepository("AppBundle:PaymentMethod")->findAllOrderedByName();
        $builder->add('paymentMethod', EntityType::class, array(
            'label' => 'Refund with',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'choices' => $activePaymentMethods,
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

        $builder->add('debitAccount', CheckboxType::class, array(
            'label' => 'Debit account with the refund',
            'required' => false
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em' => null,
        ));
    }

}