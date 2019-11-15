<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class ContactFieldType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var \AppBundle\Entity\ContactField $productField */
        $productField = $builder->getData();

        $builder->add('name', TextType::class, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $fieldTypeChoices = array(
            'Single line of text' => 'text',
            'Multiple lines of text' => 'textarea',
            'Single select menu' => 'choice',
            'Multi-select menu' => 'multiselect',
            'Check box' => 'checkbox',
        );
        if ($productField->getId()) {
            $disabled = true;
        } else {
            $disabled = false;
        }
        $builder->add('type', ChoiceType::class, array(
            'label' => 'Type',
            'required' => true,
            'choices' => $fieldTypeChoices,
            'attr' => array(
                'disabled' => $disabled
            )
        ));

        $builder->add('showOnContactList', CheckboxType::class, array(
            'label' => 'Show this field on the contact list',
            'attr' => array(
                'data-help' => '',
            )
        ));

        $builder->add('required', CheckboxType::class, array(
            'label' => 'Value must be entered before contact can be saved or items checked out.',
            'attr' => [
                'data-help' => '',
            ]
        ));

    }

}