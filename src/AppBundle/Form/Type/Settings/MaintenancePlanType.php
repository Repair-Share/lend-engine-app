<?php
namespace AppBundle\Form\Type\Settings;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenancePlanType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];

        $builder->add('name', TextType::class, [
            'label' => 'Name',
            'required' => true
        ]);

        $builder->add('interval', TextType::class, [
            'label' => 'Monthly interval (optional)',
            'required' => false
        ]);

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->em->getRepository('AppBundle:Contact');
        $contacts = $contactRepo->findAllStaff();
        $builder->add('provider', EntityType::class, array(
            'class' => 'AppBundle:Contact',
            'choices' => $contacts,
            'choice_label' => 'name',
            'label' => 'Provider',
            'required' => false,
            'attr' => [
                'data-help' => ''
            ]
        ));

        $builder->add('description', TextareaType::class, array(
            'label' => 'Description',
            'attr' => [
                'rows' => 8,
                'data-help' => "Shows as a guide for maintainers. Max 1000 characters."
            ]
        ));

        $builder->add('isActive', CheckboxType::class, array(
            'label' => 'Enabled',
            'attr' => []
        ));

        $builder->add('afterEachLoan', CheckboxType::class, array(
            'label' => 'Auto-create a single maintenance directly after each check-in',
            'attr' => [
                'data-help' => 'or set a monthly interval for regular maintenance (eg electrical tests).<br>For ad-hoc maintenance, leave both blank.'
            ]
        ));

        $builder->add('preventBorrowsIfOverdue', CheckboxType::class, array(
            'label' => 'Prevent items from being borrowed or checked out if maintenance is overdue',
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\MaintenancePlan',
            'em' => null,
        ));
    }
}