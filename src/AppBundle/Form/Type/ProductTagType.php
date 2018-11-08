<?php
// src/AppBundle/Form/Type/ProductTagType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\AbstractTranslatableType;
use AppBundle\Form\TranslatableTextType;

class ProductTagType extends AbstractTranslatableType
{
    /** @var  array */
    private $locales;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->locales = $options['locales'];

        if (count($this->locales) > 1) {
            $this->createTranslatableMapper($builder, $options)->add("name", TranslatableTextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => array(
                    'placeholder' => '',
                    'data-help' => 'You can add as many categories to an item as you like.',
                )
            ]);
        } else {
            $builder->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => array(
                    'placeholder' => '',
                    'data-help' => 'You can add as many categories to an item as you like.',
                )
            ]);
        }

        $builder->add('showOnWebsite', CheckboxType::class, array(
            'label' => 'Show on your Lend Engine site as a menu item.',
            'attr' => array(
                'data-help' => '',
            )
        ));
    }

    /**
     * This for is for products
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ProductTag',
            'locales' => null
        ));

        $this->configureTranslationOptions($resolver);
    }

}