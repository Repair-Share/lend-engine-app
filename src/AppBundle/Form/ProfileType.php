<?php

// src/AppBundle/Form/ProfileType.php

/**
 * Override the default FOSUserBundle Profile form
 *
 */
namespace AppBundle\Form;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ProfileType extends AbstractType
{
    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', TextType::class, array(
            'label' => 'form.firstName',
            'required' => true
        ));

        $builder->add('lastName', TextType::class, array(
            'label' => 'form.lastName',
            'required' => false
        ));

        $builder->add('telephone', TextType::class, array(
            'label' => 'form.telephone',
            'required' => false
        ));

        $builder->add('email', TextType::class, array(
            'label' => 'form.email',
            'required' => true,
            'attr' => array(
                'data-help' => ""
            )
        ));

        // Hide the user name (filled with email address by JS)
        $builder->add('username', HiddenType::class, array(
            'required' => true,
            'attr' => array(
                'data-help' => ""
            )
        ));

//        if ($this->container->get('service.tenant')->getLocale()) {
//            $defaultLocale = $this->container->get('service.tenant')->getLocale();
//        } else {
//            $defaultLocale = 'en';
//        }

        $languages = [
            'English'    => 'en',
            'Francais'   => 'fr',
            'Nederlands' => 'nl'
        ];
        $builder->add('locale', ChoiceType::class, array(
            'choices'  => $languages,
//            'data'     => $defaultLocale,
            'label'    => 'form.locale',
            'required' => true,
        ));



    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_profile';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'member_site'
        ));
    }
}