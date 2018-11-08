<?php
// src/AppBundle/Form/Type/MembershipType.php
namespace AppBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;


class MembershipTypeForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $yesNoChoice = [
            'Yes' => 1,
            'No' => 0
        ];

        $builder->add('name', TextType::class, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $builder->add('price', TextType::class, array(
            'label' => 'Cost',
            'required' => true,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $builder->add('duration', TextType::class, array(
            'label' => 'Length (days)',
            'required' => true,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $builder->add('discount', TextType::class, array(
            'label' => 'Discount percentage',
            'required' => false,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $builder->add('isSelfServe', ChoiceType::class, array(
            'label' => 'Allow users registering online to choose this',
            'choices' => $yesNoChoice,
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => "Typically you'd create a membership type for temporary membership, which is automatically assigned to a user when they register. This allows them to add credit and reserve items."
            )
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