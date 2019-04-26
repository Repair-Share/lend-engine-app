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

class SettingsType extends AbstractType
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

        if (!$dbData['org_timezone']) {
            $dbData['org_timezone'] = 'Europe/London';
        }

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone($dbData['org_timezone']));
        $builder->add('org_timezone', TimezoneType::class, array(
            'label' => 'Timezone (local time is '.$now->format("d M Y H:i").')',
            'required' => true,
            'data' => $dbData['org_timezone']
        ));

        $currencies = array_flip(\Symfony\Component\Intl\Intl::getCurrencyBundle()->getCurrencyNames());
        $currencies['No currency symbol'] = 'XXX';

        $builder->add('org_currency', ChoiceType::class, array(
            'label' => 'Currency',
            'choices' => $currencies,
            'required' => true,
            'data' => $dbData['org_currency'],
            'attr' => array(
                'data-help' => '',
            )
        ));

        $builder->add('org_name', TextType::class, array(
            'label' => 'Organisation name',
            'data' => $dbData['org_name'],
            'required' => true,
            'attr' => array(
                'placeholder' => '',
            )
        ));

        $builder->add('org_country', CountryType::class, array(
            'label' => 'Organisation country',
            'data' => $dbData['org_country'],
            'required' => true,
            'attr' => array(
                'placeholder' => '',
            )
        ));

        $builder->add('org_postcode', TextType::class, array(
            'label' => 'Organisation postal code',
            'data' => $dbData['org_postcode'],
            'required' => true,
            'attr' => array(
                'placeholder' => '',
                'data-help' => 'Your user map will be centred here'
            )
        ));

        $builder->add('org_email', TextType::class, array(
            'label' => 'Organisation email address',
            'data' => $dbData['org_email'],
            'required' => true,
            'attr' => array(
                'placeholder' => '',
                'data-help' => ''
            )
        ));

        $builder->add('org_address', TextareaType::class, array(
            'label' => 'Organisation address',
            'data' => $dbData['org_address'],
            'required' => true,
            'attr' => array(
                'placeholder' => '',
                'rows' => 6
            )
        ));

        $industries = [
            '' => '',
            'Electronic equipment' => 'electronics',
            'Sports equipment' => 'sports',
            'Plant and machinery' => 'plant',
            'Toys' => 'toys',
            'Tools' => 'tools',
            'Other' => 'other',
        ];
        $builder->add('industry', ChoiceType::class, array(
            'label' => 'What do you lend?',
            'required' => true,
            'choices' => $industries,
            'data' => $dbData['industry'],
            'attr' => array(
                'data-help' => '',
            )
        ));

        $autoHelp = <<<EOT
Enter a code prefix here for Lend Engine to handle your codes automatically by incrementing a number each time a new item is added,
e.g. MYCO-0023, MYCO-0024, MYCO-0025<br>
Any existing codes with the same stub will already need to be 4 digits in order for this to work.
Get in touch with us if you'd like a bulk database update.
EOT;

        $builder->add('auto_sku_stub', TextType::class, array(
            'label' => 'Item code stub',
            'data' => $dbData['auto_sku_stub'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'eg "MYCO-"',
                'data-help' => $autoHelp,
            )
        ));

        /** EMAIL AUTOMATION */

        $emailDisabled = true;
        if ($this->tenantInformationService->getFeature('EmailAutomation')) {
            $emailDisabled = false;
            $emailHelp = "";
        } else {
            $emailHelp = '<i class="fa fa-star" style="color:#ff9d00"></i> This requires a paid plan.';
            $dbData['automate_email_loan_reminder'] = false;
            $dbData['automate_email_reservation_reminder'] = false;
            $dbData['automate_email_membership'] = false;
            $dbData['automate_email_overdue_days'] = null;
        }

        $builder->add('automate_email_loan_reminder', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Send reminder the day before a loan is due back',
            'data' => (int)$dbData['automate_email_loan_reminder'],
            'attr' => [
                'class' => 'input-100',
                'data-help' => $emailHelp,
                'disabled' => $emailDisabled
            ]
        ));

        $builder->add('automate_email_reservation_reminder', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Send reminder the day before a reservation is due to be picked up',
            'data' => (int)$dbData['automate_email_reservation_reminder'],
            'attr' => [
                'class' => 'input-100',
                'data-help' => $emailHelp,
                'disabled' => $emailDisabled
            ]
        ));

        $builder->add('automate_email_membership', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Notify members when their membership has expired',
            'data' => (int)$dbData['automate_email_membership'],
            'attr' => [
                'class' => 'input-100',
                'data-help' => $emailHelp,
                'disabled' => $emailDisabled
            ]
        ));

        $builder->add('automate_email_overdue_days', TextType::class, array(
            'label' => 'Send overdue reminders after X days',
            'data' => $dbData['automate_email_overdue_days'],
            'required' => false,
            'attr' => array(
                'class' => 'input-100',
                'placeholder' => '',
                'data-help' => 'Leave blank or zero to disable automated overdue emails. '.$emailHelp,
                'disabled' => $emailDisabled
            )
        ));


        /** PAYMENT PROCESSING */

        /** @var $repo \AppBundle\Repository\PaymentMethodRepository */
        $repo =  $this->em->getRepository('AppBundle:PaymentMethod');
        $stripePaymentMethod = $repo->find($dbData['stripe_payment_method']);
        $builder->add('stripe_payment_method', EntityType::class, array(
            'label' => 'Stripe.com payment method',
            'class' => 'AppBundle:PaymentMethod',
            'choice_label' => 'name',
            'required' => false,
            'data' => $stripePaymentMethod,
            'attr' => array(
                'data-help' => "When you take a payment with this payment method, you'll be directed to the Stripe.com card processing system.",
            )
        ));

        if ($dbData['stripe_minimum_payment']) {
            $minPayment = (float)$dbData['stripe_minimum_payment'];
        } else {
            $minPayment = null;
        }
        $builder->add('stripe_minimum_payment', NumberType::class, array(
            'label' => 'Minimum payment amount via website',
            'data' => $minPayment,
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'The minimum amount of credit a member can add using Stripe via your website.',
            )
        ));

        $builder->add('stripe_use_saved_cards', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Allow users to choose from previously charged cards',
            'data' => (int)$dbData['stripe_use_saved_cards'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
            ]
        ));

        $builder->add('stripe_fee', NumberType::class, array(
            'label' => 'Fixed payment fee',
            'data' => $dbData['stripe_fee'] ? (float)$dbData['stripe_fee'] : null,
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'Added to all Stripe transactions.',
            )
        ));

        /** MAILCHIMP */

        $builder->add('mailchimp_api_key', TextType::class, array(
            'label' => 'Mailchimp API key',
            'data' => $dbData['mailchimp_api_key'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'eg 734939787364503d45xfibi34-us13',
                'data-help' => 'Mailchimp Profile > Extras > API keys',
            )
        ));

        $builder->add('mailchimp_default_list_id', TextType::class, array(
            'label' => 'Mailchimp list ID',
            'data' => $dbData['mailchimp_default_list_id'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'eg e82ca95cfd',
                'data-help' => 'Mailchimp edit list > Settings > Name & default > List ID',
            )
        ));

        $builder->add('mailchimp_double_optin', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Send double opt-in email when adding email address to Mailchimp',
            'data' => (int)$dbData['mailchimp_double_optin'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
            ]
        ));

        $builder->add('enable_waiting_list', ChoiceType::class, array(
            'choices' => $yesNoChoice,
            'label' => 'Enable waiting list',
            'data' => (int)$dbData['enable_waiting_list'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
            ]
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