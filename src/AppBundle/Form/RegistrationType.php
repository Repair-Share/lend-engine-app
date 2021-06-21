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
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Email;

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
        $settings = $this->container->get('settings');

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
            'constraints' => [
                new Email(['groups' => ['AppBundleSiteRegistration']]),
            ]
        ));

        // Honey pot for anti-spam, expect this to be blank for real users
        $builder->add('email_address', TextType::class, array(
            'label' => 'form.email',
            'required' => false,
            'constraints' => [
                new Blank(['groups' => ['AppBundleSiteRegistration']]),
            ],
            'attr' => [
                'group-class' => 'email_address'
            ]
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

        // All available languages so far translated
        $languages = [
            'Deutsch'     => 'de',
            'English'     => 'en',
            'Espanol'     => 'es',
            'Catalan'     => 'ca',
            'Francais'    => 'fr',
            'íslensku'    => 'is',
            'Nederlands'  => 'nl',
            'Română'      => 'ro',
            'Slovak'      => 'sk',
            'Slovenščina' => 'sl',
            'Svenska'     => 'sv-SE',
            'Cymraeg'     => 'cy'
        ];

        // Only whitelist the ones we have configured in settings
        $languageCodes = explode(',', $settings->getSettingValue('org_languages'));
        foreach ($languages AS $language => $code) {
            if (!in_array($code, $languageCodes)) {
                unset($languages[$language]);
            }
        }

        // Catch-all in case settings have become corrupt
        if (count($languages) == 0) {
            $languages = [
                'English'    => 'en'
            ];
        }

        $builder->add('locale', ChoiceType::class, array(
            'choices'  => $languages,
            'data'     => $this->container->get('service.tenant')->getLocale(),
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
            'data' => $this->container->get('service.tenant')->getCountry()
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
        if ($termsUri = $this->container->get('service.tenant')->getTermsUri()) {
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
        $resolver->setDefaults([
            'translation_domain' => 'member_site',
            'validation_groups' => ['AppBundleSiteRegistration']
        ]);
    }
}