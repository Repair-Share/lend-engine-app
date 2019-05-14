<?php
// src/AppBundle/Form/Type/SettingsType.php
namespace AppBundle\Form\Type\Settings;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use AppBundle\Form\Type\ToggleType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsLabelsType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    public $em;

    /** @var $tenantInformationService \AppBundle\Services\TenantService */
    private $tenantInformationService;

    /** @var \AppBundle\Services\SettingsService */
    public $settingsService;

    function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $this->tenantInformationService = $options['tenantInformationService'];
        $this->settingsService = $options['settingsService'];

        $choices = [
            'Multi purpose 19mm x 51mm (11355)' => '11355',
            'Durable 19mm x 64mm (1933085)' => '1933085'
        ];

        // Get the settings
        $dbData = $this->settingsService->getAllSettingValues();

        $builder->add('label_type', ChoiceType::class, array(
            'choices' => $choices,
            'label' => 'Label type',
            'data' => (int)$dbData['label_type'],
            'required' => true,
            'attr' => [
                'data-help' => 'Save settings after changing to update the preview.',
            ]
        ));

        $disabled = false;
        if ($this->tenantInformationService->getFeature("Labels")) {
            $dataHelp = "";
        } else {
            $dataHelp = "Label printing is only available on the Plus plan";
            $disabled = true;
        }

        $builder->add('use_labels', ToggleType::class, array(
            'expanded' => true,
            'label' => 'Turn on item label printing',
            'data' => (int)$dbData['use_labels'],
            'required' => true,
            'disabled' => $disabled,
            'attr' => [
                'data-help' => $dataHelp,
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
            'tenantInformationService' => null,
            'settingsService' => null
        ));
    }
}