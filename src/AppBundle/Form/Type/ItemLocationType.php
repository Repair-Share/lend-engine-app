<?php
// src/AppBundle/Form/Type/InventoryLocationType.php
namespace AppBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemLocationType extends AbstractType
{
    protected $em;
    protected $id;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $this->id = $options['id'];

        $builder->add('name', TextType::class, array(
            'label'    => 'Choose a name for the location',
            'required' => true,
            'attr'     => array(
                'placeholder' => 'e.g. "Store room"',
                'data-help'   => '',
            )
        ));

        $builder->add('site', EntityType::class, array(
            'label'        => 'Which site is this location in?',
            'class'        => 'AppBundle:Site',
            'choice_label' => 'name',
            'required'     => true,
            'attr'         => array(
                'data-help' => "",
            )
        ));

        $builder->add('isAvailable', CheckboxType::class, array(
            'label'    => 'Items in this location are available to loan',
            'required' => false
        ));

        // Active field

        $siteRepo = $this->em->getRepository('AppBundle:Site');
        $sites = $siteRepo->findOneBy([
            'defaultCheckInLocation' => $this->id
        ]);

        $activeFieldAttrs = array(
            'placeholder' => '',
            'data-help'   => "If you've used a location you can't delete it; you'd need to deactivate it.",
        );

        if ($sites !== null) {
            $activeFieldAttrs['onchange'] = 'if(!this.checked) { alert(\'This location is in use for xxx default check in location. Please change it before you deactivate this.\');};this.checked=true;';
        }

        $builder->add('isActive', CheckboxType::class, array(
            'label'    => 'Active?',
            'required' => false,
            'attr'     => $activeFieldAttrs
        ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('em');
        $resolver->setRequired('id');
    }

}