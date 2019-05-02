<?php
// src/AppBundle/Form/Type/SettingsType.php
namespace AppBundle\Form\Type\Settings;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsLabelsType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    public $em;

    function __construct()
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];

        $choices = [
            'Multi purpose 19mm x 51mm (11355)' => '11355',
            'Durable 19mm x 64mm (1933085)' => '1933085'
        ];

        // Get the settings
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');
        $dbData = $repo->getAllSettings();

        $builder->add('label_type', ChoiceType::class, array(
            'choices' => $choices,
            'label' => 'Label type',
            'data' => (int)$dbData['label_type'],
            'required' => true,
            'attr' => [
                'data-help' => 'Save settings after changing to update the preview.',
            ]
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em' => null
        ));
    }
}