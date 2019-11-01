<?php
// src/AppBundle/Form/Type/SettingsType.php
namespace AppBundle\Form\Type\Settings;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsTemplatesType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager  */
    public $em;

    /** @var \AppBundle\Services\SettingsService */
    public $settingsService;

    function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];
        $this->settingsService = $options['settingsService'];

        // Get the settings
        $dbData = $this->settingsService->getAllSettingValues();

        $builder->add('org_email_footer', TextareaType::class, array(
            'label' => 'HTML footer for all outbound emails',
            'data' => $dbData['org_email_footer'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'HTML in here will be added to the bottom of emails sent by Lend Engine.',
                'data-help' => 'Use "&lt;br /&gt;" as a line break.',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*   WELCOME EMAIL   */

        $builder->add('email_welcome_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_welcome_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Welcome to {organisation name}',
                'data-help' => 'The subject for public website registrations is not currently editable.'
            )
        ));

        $builder->add('email_welcome_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_welcome_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_welcome_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_welcome_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*   LOAN CONFIRMATION   */

        $builder->add('email_loan_confirmation_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_loan_confirmation_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Your loan information',
                'data-help' => "This will override the member's language settings."
            )
        ));

        $builder->add('email_loan_confirmation_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_loan_confirmation_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_loan_confirmation_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_loan_confirmation_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*   RESERVATION CONFIRMATION   */

        $builder->add('email_reserve_confirmation_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_reserve_confirmation_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Your reservation confirmation',
                'data-help' => "This will override the member's language settings."
            )
        ));

        $builder->add('email_reserve_confirmation_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_reserve_confirmation_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_reserve_confirmation_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_reserve_confirmation_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*   LOAN REMINDER   */

        $builder->add('email_loan_reminder_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_loan_reminder_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_loan_reminder_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_loan_reminder_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /* LOAN OVERDUE */

        $builder->add('email_loan_overdue_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_loan_overdue_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_loan_overdue_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_loan_overdue_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /* RESERVATION REMINDER */

        $builder->add('email_reservation_reminder_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_reservation_reminder_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_reservation_reminder_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_reservation_reminder_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*  MEMBERSHIP EXPIRY  */

        $builder->add('email_membership_expiry_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_membership_expiry_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_membership_expiry_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_membership_expiry_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*  LOAN EXTENSION */

        $builder->add('email_loan_extension_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_loan_extension_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Your loan return date has been updated',
                'data-help' => "This will override the member's language settings."
            )
        ));

        $builder->add('email_loan_extension_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_loan_extension_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_loan_extension_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_loan_extension_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        /*  DONOR NOTIFICATION EMAIL */

        $builder->add('email_donor_notification_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_donor_notification_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Your donated item has been lent out',
                'data-help' => "This will override the member's language settings."
            )
        ));

        $builder->add('email_donor_notification_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_donor_notification_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_donor_notification_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_donor_notification_foot'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
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
            'settingsService' => null
        ));
    }
}