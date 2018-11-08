<?php
// src/AppBundle/Form/Type/ItemRemoveType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ItemRemoveType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('notes', TextareaType::class, array(
            'label' => 'Notes',
            'required' => true,
            'attr' => array(
                'placeholder' => 'Enter a reason',
                'data-help' => '',
            )
        ));

    }

    /**
     * Required function for form types
     * @return string
     */
    public function getName()
    {
        return "item_remove_type";
    }
}