<?php

// src/AppBundle/Form/RegistrationType.php

/**
 * Override the default FOSUserBundle Registration form
 *
 */
namespace AppBundle\Form;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
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
        
        $translator = $this->container->get('translator');
        
        $builder->add('firstName', TextType::class, array(
            'label' => 'form.firstName',
            'required' => true,
        ));

        $builder->add('lastName', TextType::class, array(
            'label' => 'form.lastName',
            'required' => false,
        ));

        $builder->add('email', TextType::class, array(
            'label' => 'form.email',
            'required' => true,
            'attr' => array(
                'data-help' => ""
            )
        ));

        // Hide the user name (entity class overrides this with email address)
        $builder->add('username', HiddenType::class, array(
            'required' => false,
            'attr' => array(
                'data-help' => ""
            )
        ));

        $builder->add('telephone', TextType::class, array(
            'label' => 'form.telephone',
            'required' => true,
        ));

        $languages = [
            'English'    => 'en',
            'Espanol'    => 'es',
            'Francais'   => 'fr',
            'íslensku'  => 'is',
            'Nederlands' => 'nl',
            'Română'     => 'ro',
            'Slovak'     => 'sk',
            'Welsh'      => 'cy'
        ];
        $builder->add('locale', ChoiceType::class, array(
            'choices'  => $languages,
            'data'     => $this->container->get('tenant_information')->getLocale(),
            'label'    => 'form.locale',
            'required' => true,
        ));

        $builder->add('addressLine1', TextType::class, array(
            'required' => true,
            'label' => 'form.address1',
        ));
        $builder->add('addressLine2', TextType::class, array(
            'required' => true,
            'label' => 'form.address2',
        ));
        $builder->add('addressLine3', TextType::class, array(
            'required' => true,
            'label' => 'form.address3',
        ));
        $builder->add('addressLine4', TextType::class, array(
            'required' => true,
            'label' => 'form.postcode',
        ));
        $builder->add('countryIsoCode', CountryType::class, array(
            'label' => 'form.country',
            'required' => true,
            'data' => $this->container->get('tenant_information')->getCountry()
        ));

        $label = $translator->trans('public_registration.newsletter');
        $builder->add('subscriber', CheckboxType::class, array(
            'required' => false,
            'label'    => $label,
            'mapped'   => true,
            'data'     => false
        ));

        $label = $translator->trans('public_registration.terms_label', [], 'member_site');

        $termsHelp = '';
        if ($termsUri = $this->container->get('tenant_information')->getTermsUri()) {
            $linkText = $translator->trans('public_registration.terms_link', [], 'member_site');
            $termsHelp = '<a href="'.$termsUri.'" target="_blank">'.$linkText.'</a>';
        }

        $builder->add('terms', CheckboxType::class, array(
            'required' => true,
            'label'    => $label,
            'mapped'   => false,
            'attr'     => [
                'data-help' => $termsHelp
            ]
        ));

    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'member_site',
            'validation_groups' => ['AppBundleRegistration']
        ));
    }
}