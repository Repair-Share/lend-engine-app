<?php
// src/AppBundle/Form/Type/SettingsType.php
namespace AppBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsMemberSiteType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    public $em;

    /** @var \AppBundle\Extensions\TenantInformation */
    public $tenantInformationService;

    function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $this->tenantInformationService = $options['tenantInformationService'];

        $yesNoChoice = [
            'Yes' => 1,
            'No' => 0
        ];

        // Get the settings
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');
        $dbData = $repo->getAllSettings();

        $languages = [
            'Deutsch'    => 'de',
            'English'    => 'en',
            'Espanol'    => 'es',
            'Francais'   => 'fr',
            'íslensku'   => 'is',
            'Nederlands' => 'nl',
            'Română'     => 'ro',
            'Slovak'     => 'sk',
            'Svenska'    => 'se',
            'Welsh'      => 'cy'
        ];
        $builder->add('org_locale', ChoiceType::class, array(
            'label' => 'Default language',
            'choices' => $languages,
            'data' => $dbData['org_locale'],
            'attr' => array(
                'data-help' => "Currently only the member site is translated.
                If you'd like an additional language, just get in touch with us.
                Changing this will update the default language for all your existing contacts too.",
            )
        ));

        $lingoData = explode(',', $dbData['org_languages']);
        if ($this->tenantInformationService->getFeature('MultipleLanguages')) {
            $multi = true;
            $lingoHelp = "Choose multiple languages here if your members require a choice. You'll be able to add item names and descriptions in each language.";
        } else {
            $multi = false;
            $lingoData = $lingoData[0];
            $lingoHelp = '<i class="fa fa-star" style="color:#ff9d00"></i> On higher pay plans you can choose to display item information in more than one language.';
        }
        $builder->add('org_languages', ChoiceType::class, array(
            'label' => 'Display language(s) for item names and descriptions',
            'choices' => $languages,
            'multiple' => $multi,
            'required' => true,
            'data' => $lingoData,
            'attr' => array(
                'data-help' => $lingoHelp,
            )
        ));

        $builder->add('site_welcome_user', TextareaType::class, array(
            'label' => 'Successful registration page content (HTML)',
            'data' => $dbData['site_welcome_user'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => 'This page shows when a user confirms their email address.
Save and then <a target="_blank" href="/member/welcome">preview here</a>.
If you have any self-serve membership types,
then the user will also be shown a button to continue to choose a membership.',
                'rows' => 6,
                'class' => 'summernote'
            )
        ));

        $readOnlyCustomTheme = true;
        $planStarHtml = '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Plus plan.';
        if ($this->tenantInformationService->getFeature('CustomTheme')) {
            $readOnlyCustomTheme = false;
            $planStarHtml = '';
        }
        $builder->add('site_description', TextareaType::class, array(
            'label' => 'Website meta description',
            'data' => $dbData['site_description'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Meta description for search engines.',
                'rows' => 3,
                'data-help' => $planStarHtml,
                'readonly' => $readOnlyCustomTheme
            )
        ));

        $readOnlyCSS = true;
        $planStarHtml = '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Standard plan and above.';
        if ($this->tenantInformationService->getFeature('CustomStyle')) {
            $readOnlyCSS = false;
            $planStarHtml = '';
        }
        $builder->add('site_css', TextareaType::class, array(
            'label' => 'Website custom CSS',
            'data' => $dbData['site_css'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Enter CSS here that will appear on each page of your public website.',
                'data-help' => $planStarHtml,
                'rows' => 6,
                'readonly' => $readOnlyCSS
            )
        ));

        $readOnlyJs = true;
        if ($this->tenantInformationService->getFeature('CustomStyle')) {
            $readOnlyJs = false;
            $planStarHtml = '';
        }
        $builder->add('site_js', TextareaType::class, array(
            'label' => 'Website custom Javascript',
            'data' => $dbData['site_js'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Enter Javascript here that will appear in each page of your member site. jQuery is OK.',
                'data-help' => $planStarHtml.' Be VERY careful with this field as incorrect Javascript can stop your site working.',
                'rows' => 6,
                'readonly' => $readOnlyJs
            )
        ));

        $readOnlyFontName = true;
        if ($this->tenantInformationService->getFeature('CustomStyle')) {
            $readOnlyFontName = false;
            $planStarHtml = '';
        }
        $builder->add('site_font_name', TextType::class, array(
            'label' => 'Website font name (from Google fonts)',
            'data' => $dbData['site_font_name'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'eg "Titillium Web".',
                'data-help' => $planStarHtml.' Go to https://fonts.google.com and choose a font name to customise your member site.',
                'readonly' => $readOnlyFontName
            )
        ));

        $builder->add('site_allow_registration', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Allow member registration via the public website',
            'data' => (int)$dbData['site_allow_registration'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
            ]
        ));

        // Only on standard plans
        if ($this->tenantInformationService->getFeature('PrivateSite')) {
            $builder->add('site_is_private', ChoiceType::class, array(
                'choices' => $yesNoChoice,
                'label' => 'Require users to log in before they can view items',
                'data' => (int)$dbData['site_is_private'],
                'required' => true,
                'attr' => array(
                    'class' => 'input-100',
                )
            ));
        } else {
            $noChoice = [
                'No' => 0
            ];
            $builder->add('site_is_private', ChoiceType::class, array(
                'choices' => $noChoice,
                'label' => 'Require users to log in before they can view items',
                'data' => (int)$dbData['site_is_private'],
                'required' => true,
                'attr' => array(
                    'class' => 'input-100 upgrade',
                    'data-help' => '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Standard plan and above.'
                )
            ));
        }

        $builder->add('registration_terms_uri', TextType::class, array(
            'label' => 'URL to your Terms and Conditions page',
            'data' => $dbData['registration_terms_uri'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'A full domain including http://',
                'data-help' => 'Provided as a link for users on the registration form.',
            )
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em' => null,
            'tenantInformationService' => null
        ));
    }
}