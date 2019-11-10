<?php
// src/AppBundle/Form/Type/ItemSector.php
namespace AppBundle\Form\Type;

use AppBundle\Entity\Contact;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ItemType extends AbstractType
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var array */
    private $customFields;

    /** @var array */
    private $customFieldValues;

    /** @var integer */
    private $donatedBy;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];
        $this->customFields = $options['customFields'];
        $this->customFieldValues = $options['customFieldValues'];
        $this->donatedBy = $options['donatedBy'];

        /** @var \AppBundle\Entity\InventoryItem $product */
        $product = $builder->getData();

        /** @var $siteRepo \AppBundle\Repository\SiteRepository */
        $siteRepo = $this->em->getRepository('AppBundle:Site');
        $sites = $siteRepo->findAll();

        $builder->add("name", TextType::class);

        $builder->add('description', TextareaType::class, array(
            'label' => 'Full description (shown online)',
            'required' => false,
            'attr' => array(
                'placeholder' => 'Detailed information about this item. Shown on the public website.',
                'rows' => 6,
                'data-help' => 'www links are made clickable on member site'
            )
        ));

        $builder->add('careInformation', TextareaType::class, array(
            'label' => 'Care information (admin only)',
            'required' => false,
            'attr' => array(
                'placeholder' => "Use this field to tell your team how to clean / prepare an item for the next loan. It's not shown to members.",
                'rows' => 6,
                'data-help' => ''
            )
        ));

        $builder->add('componentInformation', TextareaType::class, array(
            'label' => 'Components / contents (shown online)',
            'required' => false,
            'attr' => array(
                'placeholder' => "Use this field to store information that is sent to members in the loan confirmation and loan reminder emails. Shown on the public website.",
                'rows' => 6,
                'data-help' => 'www links are made clickable on member site'
            )
        ));

        if (!$product->getId()) {
            // Get valid locations for "add" workflow (excludes on-loan and reserved locations)
            /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
            $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');
            $locations = $locationRepo->findOrderedByName('notOnLoan');

            if (count($sites) > 1) {
                $choice_label = 'nameWithSite';
            } else {
                $choice_label = 'name';
            }
            $builder->add('inventoryLocation', EntityType::class, array(
                'class' => 'AppBundle:InventoryLocation',
                'choices' => $locations,
                'choice_label' => $choice_label,
                'empty_data'  => '',
                'label' => 'Add to location',
                'required' => true,
                'mapped' => true,
                'attr' => array(
                    'data-name' > 'location'
                )
            ));
        }

        // Editing item sector is a separate process due to the large number of types possible, so hide the field
        // We do it in a separate full screen UI
        $builder->add('itemSector', HiddenType::class, array(
            'mapped' => false,
            'data' => $options['itemSectorId']
        ));

        $builder->add('sku', TextType::class, array(
            'label' => 'Code',
            'required' => false,
            'attr' => array(
                'placeholder' => ''
            )
        ));

        $builder->add('note', TextType::class, array(
            'label' => 'Short description (admin only)',
            'required' => false,
            'attr' => array(
                'data-help' => 'Appears on loans and item list.'
            )
        ));

        $builder->add('imageName', HiddenType::class, array(
            'required' => false
        ));

        $builder->add('loanFee', TextType::class, array(
            'label' => 'Loan fee',
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'data-help' => ''
            )
        ));

        $builder->add('maxLoanDays', TextType::class, array(
            'label' => 'Loan period',
            'required' => false,
            'attr' => array(
                'placeholder' => '(days)',
                'data-help' => ''
            )
        ));

        $builder->add('depositAmount', TextType::class, array(
            'label' => 'Deposit amount',
            'required' => false,
            'attr' => [
                'class' => '',
                'data-help' => ''
            ]
        ));

        $builder->add('priceCost', TextType::class, array(
            'label' => 'Price paid',
            'required' => false,
            'attr' => array(
                'placeholder' => ""
            )
        ));

        $builder->add('priceSell', TextType::class, array(
            'label' => 'Value / RRP',
            'required' => false,
            'attr' => array(
                'placeholder' => ""
            )
        ));

        $builder->add('showOnWebsite', CheckboxType::class, array(
            'label' => 'Show this item on your Lend Engine member site',
            'required' => false,
            'attr' => array(
                'placeholder' => ""
            )
        ));

        $builder->add('isReservable', CheckboxType::class, array(
            'label' => 'This item can be reserved by members online',
            'required' => false,
            'attr' => array(
                'data-help' => "When logged in to member site as admin, all items are reservable online."
            )
        ));

        /** @var $conditionRepo \AppBundle\Repository\ItemConditionRepository */
        $conditionRepo = $this->em->getRepository('AppBundle:ItemCondition');
        $conditions = $conditionRepo->findAllOrderedBySort();
        $builder->add('condition', EntityType::class, array(
            'class' => 'AppBundle:ItemCondition',
            'choices' => $conditions,
            'choice_label' => 'name',
            'label' => 'Condition of item',
            'required' => true,
        ));

        $builder->add('serial', TextType::class, array(
            'label' => 'Serial number',
            'required' => false,
            'attr'=> array(
                'data-help' => ''
            )
        ));

        $builder->add('keywords', TextType::class, array(
            'label' => 'Keywords (for search)',
            'required' => false,
            'attr'=> array(
                'data-help' => ''
            )
        ));

        $builder->add('brand', TextType::class, array(
            'label' => 'Brand / Manufacturer',
            'required' => false,
            'attr'=> array(
                'data-help' => ''
            )
        ));

        /** @var $tagRepo \AppBundle\Repository\ProductTagRepository */
        $tagRepo = $this->em->getRepository('AppBundle:ProductTag');
        $tags = $tagRepo->findAllOrderedByName();
        $builder->add('tags', EntityType::class, array(
            'class' => 'AppBundle:ProductTag',
            'choice_label' => 'name',
            'label' => 'Category',
            'choices' => $tags,
            'required' => false,
            'multiple' => true,
            'attr' => array(
                'class' => '',
                'data-help' => 'Choose more than one if you need. Also used for navigation on your member site. Add more in settings.',
            )
        ));

        /** @var $maintenancePlanRepo \AppBundle\Repository\MaintenancePlanRepository */
        $maintenancePlanRepo = $this->em->getRepository('AppBundle:MaintenancePlan');
        $maintenancePlans = $maintenancePlanRepo->findAllOrderedByName();
        $builder->add('maintenancePlans', EntityType::class, array(
            'class' => 'AppBundle:MaintenancePlan',
            'choice_label' => 'fullName',
            'label' => 'Maintenance applicable for this item',
            'choices' => $maintenancePlans,
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'attr' => [
                'class' => '',
                'data-help' => '',
            ]
        ));

        /** @var $checkOutPromptRepo \AppBundle\Repository\CheckOutPromptRepository */
        $checkOutPromptRepo = $this->em->getRepository('AppBundle:CheckOutPrompt');
        $checkOutPrompts = $checkOutPromptRepo->findAllOrderedBySort();
        $builder->add('checkOutPrompts', EntityType::class, array(
            'class' => 'AppBundle:CheckOutPrompt',
            'choice_label' => 'name',
            'choices' => $checkOutPrompts,
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'attr' => array(
                'class' => '',
                'data-help' => 'Prompt the user to complete item-related tasks when checking a loan out, such as "Deposit taken" or "Safety waiver signed".',
            )
        ));

        /** @var $checkInPromptRepo \AppBundle\Repository\CheckInPromptRepository */
        $checkInPromptRepo = $this->em->getRepository('AppBundle:CheckInPrompt');
        $checkInPrompts = $checkInPromptRepo->findAllOrderedBySort();
        $builder->add('checkInPrompts', EntityType::class, array(
            'class' => 'AppBundle:CheckInPrompt',
            'choice_label' => 'name',
            'choices' => $checkInPrompts,
            'required' => false,
            'multiple' => true,
            'expanded' => true,
            'attr' => array(
                'class' => '',
                'data-help' => 'Prompt the user when checking an item in, such as "Confirmed wheels still turn".',
            )
        ));

        if (count($sites) > 1) {
            $builder->add('sites', EntityType::class, array(
                'label' => 'Restrict this item to only the following sites',
                'class' => 'AppBundle:Site',
                'choice_label' => 'name',
                'choices' => $sites,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => array(
                    'class' => '',
                    'data-help' => 'Restrict this item to these sites. <br>Messages will be included in email confirmations to request members return the item to one of these sites.<br>Select none, or all sites, for no restrictions.',
                )
            ));
        }

        foreach ($this->customFields AS $field) {
            /** @var $field \AppBundle\Entity\ProductField */
            $fieldId = $field->getId();

            if (isset($this->customFieldValues[$fieldId])) {
                /** @var \AppBundle\Entity\ProductFieldValue $productFieldValue */
                $productFieldValue = $this->customFieldValues[$fieldId];
                $defaultData = $productFieldValue->getFieldValue();
            } else {
                $defaultData = '';
            }

            $fieldName = $field->getName();
            $fieldHelp = '';

            if ($field->getShowOnWebsite() == true) {
                $fieldName .= ' *';
            }

            if ($field->getType() == 'choice') {
                $choices = $field->getChoices();
                $choiceArray = array();
                foreach ($choices AS $choice) {
                    $choiceArray[$choice->getOptionName()] = $choice->getId();
                }
                $builder->add('fieldValue'.$fieldId, ChoiceType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'choices' => $choiceArray,
                    'data' => $defaultData,
                    'mapped' => false,
                    'attr' => [
                        'data-help' => $fieldHelp
                    ]
                ));
            } else if ($field->getType() == 'multiselect') {
                $choices = $field->getChoices();
                $choiceArray = array();
                foreach ($choices AS $choice) {
                    $choiceArray[$choice->getOptionName()] = $choice->getId();
                }
                $defaultData = explode(',', $defaultData);
                $builder->add('fieldValue'.$fieldId, ChoiceType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'choices' => $choiceArray,
                    'data' => $defaultData,
                    'mapped' => false,
                    'multiple' => true,
                    'attr' => [
                        'data-help' => $fieldHelp
                    ]
                ));
            } else if ($field->getType() == 'date') {
                $builder->add('fieldValue'.$fieldId, TextType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'data' => $defaultData,
                    'mapped' => false,
                    'attr' => ['class' => 'single-date-picker', 'data-help' => $fieldHelp]
                ));
            } else if ($field->getType() == 'checkbox') {
                if (!$defaultData) {
                    $defaultData = false;
                } else {
                    $defaultData = true;
                }
                $builder->add('fieldValue'.$fieldId, CheckboxType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'data' => $defaultData,
                    'mapped' => false,
                    'attr' => [
                        'data-help' => $fieldHelp
                    ]
                ));
            } else if ($field->getType() == 'text') {
                $builder->add('fieldValue'.$fieldId, TextType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'data' => $defaultData,
                    'mapped' => false,
                    'attr' => [
                        'data-help' => $fieldHelp
                    ]
                ));
            } else if ($field->getType() == 'textarea') {
                $builder->add('fieldValue'.$fieldId, TextareaType::class, array(
                    'label' => $fieldName,
                    'required' => $field->getRequired(),
                    'data' => $defaultData,
                    'mapped' => false,
                    'attr' => [
                        'data-help' => $fieldHelp
                    ]
                ));
            }

        }

        $formModifier = function (FormInterface $form, $donatedBy = null, $ownedBy = null) {
            $choices = null == $donatedBy ? [] : [$donatedBy];
            $form->add('donatedBy', EntityType::class, [
                'class' => 'AppBundle:Contact',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'ajax-contact',
                    'data-help' => 'Search for existing contacts. Donor is notified each time the item is checked out. Edit the email at Settings &raquo; Templates.'
                ],
                'required' => false,
                'choices' => $choices,
            ]);

            $choices = null == $ownedBy ? [] : [$ownedBy];
            $form->add('ownedBy', EntityType::class, [
                'class' => 'AppBundle:Contact',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'ajax-contact',
                    'data-help' => 'If your library shares community items.'
                ],
                'required' => false,
                'choices' => $choices,
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $form = $event->getForm();

                /** @var $contactRepo \AppBundle\Repository\ContactRepository */
                $contactRepo = $this->em->getRepository('AppBundle:Contact');

                if (isset($data['donatedBy']) && $data['donatedBy']) {
                    $donatedBy = $contactRepo->find($data['donatedBy']);
                } else {
                    $donatedBy = null;
                }

                if (isset($data['ownedBy']) && $data['ownedBy']) {
                    $ownedBy = $contactRepo->find($data['ownedBy']);
                } else {
                    $ownedBy = null;
                }

                $form->remove('donatedBy');
                $form->remove('ownedBy');

                $formModifier($event->getForm(), $donatedBy, $ownedBy);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $formModifier($event->getForm(), $data->getDonatedBy(), $data->getOwnedBy());
            }
        );

    }

    /**
     * This for is for products
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\InventoryItem',
            'em' => null,
            'itemSectorId' => null,
            'donatedBy' => null,
            'customFields' => null,
            'customFieldValues' => null
        ));
    }

}