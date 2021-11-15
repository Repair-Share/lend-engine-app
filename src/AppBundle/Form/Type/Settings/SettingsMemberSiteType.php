<?php
// src/AppBundle/Form/Type/SettingsType.php
namespace AppBundle\Form\Type\Settings;

use AppBundle\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsMemberSiteType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    public $em;

    /** @var \AppBundle\Services\TenantService */
    public $tenantService;

    /** @var \AppBundle\Services\SettingsService */
    public $settingsService;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $this->tenantService = $options['tenantService'];
        $this->settingsService = $options['settingsService'];

        // Get the settings
        $dbData = $this->settingsService->getAllSettingValues();

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
            'Cymraeg'     => 'cy',
            'Ukrainian'   => 'uk-UA'
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
        $multi = true;
        $lingoHelp = "Choose multiple languages here if your members require a choice when registering or browsing.";
        $builder->add('org_languages', ChoiceType::class, array(
            'label' => 'Language(s) that members can choose from',
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
                'data-help' => 'This page shows when a user confirms their email address, 
or completes the registration form if you have email confirmation turned off.
Save and then <a target="_blank" href="/member/welcome">preview here</a>.
If you have any self-serve membership types,
then the user will also be shown a button to continue to choose a membership.',
                'rows' => 6,
                'class' => 'summernote'
            )
        ));

        $builder->add('page_registration_header', TextareaType::class, array(
            'label' => 'Content to show above the registration form (HTML)',
            'data' => $dbData['page_registration_header'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 6,
                'class' => 'summernote'
            )
        ));

        $readOnlyCustomTheme = true;
        $planStarHtml = '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Plus plan.';
        if ($this->tenantService->getFeature('CustomTheme')) {
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
        $planStarHtml = '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Starter plan and above.';
        if ($this->tenantService->getFeature('CustomStyle')) {
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
        if ($this->tenantService->getFeature('CustomStyle')) {
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
        if ($this->tenantService->getFeature('CustomStyle')) {
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

        $builder->add('print_css', TextareaType::class, array(
            'label' => 'CSS for printed catalogue',
            'data' => $dbData['print_css'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'To style the printable version of the member site (for admins only)',
                'rows' => 6
            )
        ));

        $builder->add('site_domain', TextType::class, [
            'label' => 'Custom domain',
            'data' => $dbData['site_domain'],
            'required' => false,
            'attr' => [
                'placeholder' => 'eg "hire.mylibrary.com"',
                'data-help' => '',
            ]
        ]);

        $builder->add('group_similar_items', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Group items with the same name into one search result',
            'data' => (int)$dbData['group_similar_items'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "If any one of the group is available, the grouped item will show as available."
            ]
        ));

        $builder->add('registration_require_email_confirmation', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Require email confirmation before user can log in.',
            'data' => (int)$dbData['registration_require_email_confirmation'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "If this is turned on, users will need to click a link in a confirmation email before registration is complete."
            ]
        ));

        $builder->add('self_checkout', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Allow members to check items in and out',
            'data' => (int)$dbData['self_checkout'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
                'data-help' => ""
            ]
        ));

        $builder->add('self_extend', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Allow members to change loan return dates themselves.',
            'data' => (int)$dbData['self_extend'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "Any fees due will need to be paid using the Stripe integration."
            ]
        ));

        $builder->add('site_allow_registration', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Allow member registration via the public website',
            'data' => (int)$dbData['site_allow_registration'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
            ]
        ));

        // Only on standard plans
        if ($this->tenantService->getFeature('PrivateSite')) {
            $choices = ['Yes' => '1', 'No'  => '0',];
            $builder->add('site_is_private', ToggleType::class, array(
                'expanded' => true,
                'choices' => $choices,
                'label' => 'Require users to log in before they can view items',
                'data' => (int)$dbData['site_is_private'],
                'required' => true,
                'attr' => array(
                    'class' => 'input-100',
                )
            ));
        } else {
            $choices = ['No' => 0];
            $builder->add('site_is_private', ToggleType::class, array(
                'choices' => $choices,
                'expanded' => true,
                'label' => 'Require users to log in before they can view items',
                'data' => (int)$dbData['site_is_private'],
                'required' => true,
                'attr' => array(
                    'class' => 'input-100 upgrade',
                    'data-help' => '<i class="fa fa-star" style="color:#ff9d00"></i> Only available on Starter plan and above.'
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

        $builder->add('google_tracking_id', TextType::class, array(
            'label' => 'Google Analytics tracking ID',
            'data' => $dbData['google_tracking_id'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'eg UA-7133303-X',
                'data-help' => 'Adds a Global Site Tag (gtag.js) to your member site for visitor analytics.',
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
            'tenantService' => null,
            'settingsService' => null,
        ));
    }
}