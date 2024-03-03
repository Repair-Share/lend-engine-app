<?php
namespace AppBundle\Form\Type\Settings;

use AppBundle\Form\Type\SiteOpeningType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SiteType extends AbstractType
{
    protected $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        $forwardPicking = false;
        if ($setting = $repo->findOneBy(['setupKey' => 'forward_picking'])) {

            if ($setting->getSetupValue()) {
                $forwardPicking = true;
            }

        }



        $site = $builder->getData();

        $builder->add('name', TextType::class, array(
            'label' => 'Name',
            'required' => true,
            'attr' => array(
                'placeholder' => 'e.g. "North city library"'
            )
        ));

        $builder->add('colour', TextType::class, array(
            'label' => 'Colour',
            'required' => false,
            'attr' => array(
                'placeholder' => 'HTML code eg "#9db2eb"',
                'data-help' => 'Used as a key on booking calendars.'
            )
        ));

        if ($siteId = $site->getId()) {
            $builder->add('isActive', CheckboxType::class, array(
                'label' => 'This site is active',
                'required' => false,
                'attr' => array(
                    'placeholder' => '',
                    'data-help' => "If you've used a site for items or loans, you can't delete it. You'd need to deactivate it.",
                )
            ));
        }

        $builder->add('address', TextareaType::class, array(
            'label' => 'Address',
            'required' => false
        ));

        $builder->add('post_code', TextType::class, array(
            'label' => 'Postal code',
            'required' => true
        ));

        $builder->add('country', CountryType::class, array(
            'label' => 'Country',
            'required' => false
        ));

        $builder->add('siteOpenings', CollectionType::class, array(
            'entry_type' => SiteOpeningType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => false
        ));

        if ($siteId = $site->getId()) {

            /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
            $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');
            $locations = $locationRepo->findOrderedByName('notOnLoan', $siteId);

            $builder->add('default_check_in_location', EntityType::class, array(
                'label' => 'Default check in location',
                'class' => 'AppBundle:InventoryLocation',
                'choices' => $locations,
                'choice_label' => 'nameWithSite',
                'required' => true,
                'attr' => array(
                    'data-help' => "When checking items back in from a loan, we'll set this option for you.",
                )
            ));

            if ($forwardPicking) {

                $builder->add('default_forward_pick_location', EntityType::class, array(
                    'label' => 'Default forward pick location',
                    'class' => 'AppBundle:InventoryLocation',
                    'choices' => $locations,
                    'placeholder' => 'Not set',
                    'choice_label' => 'nameWithSite',
                    'required' => false,
                    'attr' => array(
                        'data-help' => "When forward picking is enabled you can set the forward picking location here",
                    )
                ));

            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Site',
            'em' => null
        ));
    }

}