<?php
// src/AppBundle/Form/Type/ItemMoveType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ItemMoveType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];

        // Get valid locations for "move" workflow (excludes on-loan)
        /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
        $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');
        $locations = $locationRepo->findOrderedByName('notOnLoan');
        $defaultValue = $options['location'];

        $sites = $this->em->getRepository('AppBundle:Site')->findAll();
        if (count($sites) > 1) {
            $choiceLabel = 'nameWithSite';
        } else {
            $choiceLabel = 'name';
        }

        $builder->add('location', EntityType::class, array(
            'class' => 'AppBundle:InventoryLocation',
            'choices' => $locations,
            'choice_label' => $choiceLabel,
            'label' => 'Move to:',
            'required' => true,
            'data' => $defaultValue,
            'attr' => [
                'data-name' => 'Location'
            ]
        ));

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->em->getRepository('AppBundle:Contact');
        $contacts = $contactRepo->findAllStaff();
        if ($options['assignedTo'] && 0) {
            $defaultValue = $options['assignedTo'];
        } else {
            $defaultValue = '';
        }
        $builder->add('contact', EntityType::class, array(
            'class' => 'AppBundle:Contact',
            'choices' => $contacts,
            'choice_label' => 'name',
            'label' => 'Assign to:',
            'required' => false,
            'data' => $defaultValue,
            'attr' => [
                'data-name' => 'Assigned to',
                'data-help' => ''
            ]
        ));

        $builder->add('cost', TextType::class, array(
            'label' => 'Add a cost:',
            'required' => false,
            'attr' => [
                'placeholder' => '0.00',
                'data-help' => '',
                'size' => 5
            ]
        ));

        $builder->add('notes', TextareaType::class, array(
            'label' => 'Notes',
            'required' => false,
            'attr' => [
                'placeholder' => 'e.g. "Dirty, needs a clean"',
                'data-help' => '',
            ]
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em'         => null,
            'location'   => null,
            'assignedTo' => null
        ));
    }
}