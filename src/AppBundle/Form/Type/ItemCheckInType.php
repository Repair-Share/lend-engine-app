<?php
// src/AppBundle/Form/Type/ItemCheckInType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

class ItemCheckInType extends AbstractType
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var integer
     */
    private $defaultCheckInLocation;

    /**
     * @var
     */
    private $lateFee;

    /**
     * @var \AppBundle\Entity\Site
     */
    private $activeSite;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];
        $this->defaultCheckInLocation = $options['defaultCheckInLocation'];
        $this->lateFee = $options['lateFee'];
        $this->activeSite = $options['activeSite'];

        // Get valid locations for "check in" workflow (excludes on-loan)
        /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
        $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');

        if ($this->activeSite) {
            $siteId = $this->activeSite->getId();
            $formHelp = 'You are working at "'.$this->activeSite->getName().'". <a href="/admin/choose-site" class="modal-link">Change</a>.';
        } else {
            $siteId = 1;
            $formHelp = '';
        }

        $locations    = $locationRepo->findOrderedByName('notOnLoan', $siteId);
        $defaultValue = $locations[0];
        if ($this->defaultCheckInLocation) {
            $location = $locationRepo->find($this->defaultCheckInLocation);
            $defaultValue = $location;
        }

        $builder->add('location', EntityType::class, array(
            'class' => 'AppBundle:InventoryLocation',
            'choices' => $locations,
            'choice_label' => 'name',
            'label' => 'Check in to location',
            'required' => true,
            'data' => $defaultValue,
            'attr' => [
                'data-name' => 'Location',
                'data-help' => $formHelp
            ]
        ));

        /** @var $contactRepo \AppBundle\Repository\ContactRepository */
        $contactRepo = $this->em->getRepository('AppBundle:Contact');
        $contacts = $contactRepo->findAllStaff();
        $builder->add('contact', EntityType::class, array(
            'class' => 'AppBundle:Contact',
            'choices' => $contacts,
            'choice_label' => 'name',
            'label' => 'Assign to',
            'required' => false,
            'attr' => [
                'data-name' => 'Assigned to',
                'data-help' => 'This will send an email notification.'
            ]
        ));

        $builder->add('feeAmount', TextType::class, array(
            'label' => 'Charge a fee',
            'required' => false,
            'data' => $this->lateFee,
            'attr' => [
                'class' => ''
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
            'em' => null,
            'defaultCheckInLocation' => null,
            'lateFee' => null,
            'activeSite' => null
        ));
    }
}