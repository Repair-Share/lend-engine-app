<?php
// src/AppBundle/Form/Type/ChildType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChildType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, array(
            'label' => 'Name'
        ));

        $choices = [
            'Male' => 'm',
            'Female' => 'f',
            'Not given' => '-'
        ];
        $builder->add('gender', ChoiceType::class, array(
            'choices' => $choices,
            'label' => 'Gender',
        ));

        $years = [];
        $y = date("Y");
        for ($n = 2000; $n < $y+1; $n++) {
            $years[] = $n;
        }
        $builder->add('dateOfBirth', DateType::class, array(
            'label' => 'Date of birth',
            'required' => true,
            'years' => $years,
            'format' => 'dd MMM yyyy',
            'attr' => array(
                'placeholder' => '',
                'class' => 'no-select2'
            )
        ));

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Child',
        ));
    }

    /**
     * Required function for form types
     * @return string
     */
    public function getName()
    {
        return "child_type";
    }
}