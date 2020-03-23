<?php
namespace AppBundle\Form\Type\Settings;

use AppBundle\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsEventsType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    public $em;

    /** @var \AppBundle\Services\TenantService */
    public $tenantService;

    /** @var \AppBundle\Services\SettingsService */
    public $settingsService;

    function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $this->tenantService = $options['tenantService'];
        $this->settingsService = $options['settingsService'];

        // Get the settings
        $dbData = $this->settingsService->getAllSettingValues();

        $builder->add('show_events_online', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Show a listing of published events on your member site',
            'data' => (int)$dbData['show_events_online'],
            'required' => true,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "If the event is bookable, users will be able to book online."
            ]
        ));

        $builder->add('page_event_header', TextareaType::class, array(
            'label' => 'Content to show at the top of the event listing page',
            'data' => $dbData['page_event_header'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 6,
                'class' => 'summernote'
            )
        ));

        $builder->add('event_time_step', NumberType::class, array(
            'label' => 'Time interval (minutes) for start and end time choice',
            'data' => (int)$dbData['event_time_step'],
            'required' => false,
            'attr' => [
                'class' => 'input-100',
                'data-help' => 'Controls the options you get in the time selector on the event edit screen. Between 1 and 60.'
            ]
        ));

        if ($this->tenantService->getFeature('CustomEmail')) {
            $help = '';
            $disabled = false;
        } else {
            $help = '<i class="fa fa-star" style="color:#ff9d00"></i> Editable templates are not available on your plan. Upgrade at Settings &raquo; Billing.';
            $disabled = true;
        }
        $builder->add('email_booking_confirmation_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_booking_confirmation_subject'],
            'required' => false,
            'disabled' => $disabled,
            'attr' => array(
                'placeholder' => 'Booking confirmation',
                'data-help' => $help
            )
        ));

        $builder->add('email_booking_confirmation_head', TextareaType::class, array(
            'label' => 'Additional header content (HTML allowed)',
            'data' => $dbData['email_booking_confirmation_head'],
            'required' => false,
            'disabled' => $disabled,
            'attr' => array(
                'placeholder' => '',
                'data-help' => $help,
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_booking_confirmation_foot', TextareaType::class, array(
            'label' => 'Additional footer content (HTML allowed)',
            'data' => $dbData['email_booking_confirmation_foot'],
            'required' => false,
            'disabled' => $disabled,
            'attr' => array(
                'placeholder' => '',
                'data-help' => $help,
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
            'tenantService' => null,
            'settingsService' => null,
        ));
    }
}