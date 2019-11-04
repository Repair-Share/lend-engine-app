<?php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class MaintenanceType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->em->getRepository('AppBundle:Contact');
        $contacts = $contactRepo->findAllStaff();
        $builder->add('assignedTo', EntityType::class, array(
            'class' => 'AppBundle:Contact',
            'choices' => $contacts,
            'choice_label' => 'name',
            'label' => 'Assign to:',
            'required' => false,
            'attr' => [
                'data-name' => 'Assigned to',
                'data-help' => ''
            ]
        ));

        $choices = [
            'Overdue' => 'overdue',
            'Planned' => 'planned',
            'In progress' => 'in_progress',
            'Completed' => 'completed',
            'Skipped' => 'skipped',
        ];
        $builder->add('status', ChoiceType::class, array(
            'label' => 'Status',
            'choices' => $choices,
            'required' => true,
            'attr' => [
                'data-help' => '',
            ]
        ));

        $builder->add('totalCost', CurrencyamountType::class, array(
            'label' => 'Total cost',
            'required' => false,
            'attr' => [
                'placeholder' => '0.00',
            ]
        ));

        $builder->add('notes', TextareaType::class, array(
            'label' => 'Notes',
            'required' => false,
            'attr' => [
                'placeholder' => '',
                'data-help' => 'Edit this field if you need to add more notes later.',
                'rows' => '10'
            ]
        ));

        $builder->add('createNext', CheckboxType::class, array(
            'label' => 'Auto-schedule next maintenance',
            'required' => false,
            'mapped' => false,
            'data' => false,
            'attr' => [
                'data-help' => '',
            ]
        ));

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em' => null,
        ));
    }
}