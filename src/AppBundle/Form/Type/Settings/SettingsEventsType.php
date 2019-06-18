<?php
namespace AppBundle\Form\Type\Settings;

use AppBundle\Form\Type\ToggleType;
use Symfony\Component\Form\AbstractType;
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

        $builder->add('email_booking_confirmation_subject', TextType::class, array(
            'label' => 'Subject',
            'data' => $dbData['email_booking_confirmation_subject'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'Booking confirmation',
                'data-help' => ''
            )
        ));

        $builder->add('email_booking_confirmation_head', TextareaType::class, array(
            'label' => 'Header',
            'data' => $dbData['email_booking_confirmation_head'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => '',
                'rows' => 4,
                'class' => 'limited'
            )
        ));

        $builder->add('email_booking_confirmation_foot', TextareaType::class, array(
            'label' => 'Footer',
            'data' => $dbData['email_booking_confirmation_foot'],
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
            'tenantService' => null,
            'settingsService' => null,
        ));
    }
}