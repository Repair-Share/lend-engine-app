<?php
// src/AppBundle/Form/Type/ContactType.php
namespace AppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $contact = $builder->getData();

        $customFields           = $options['customFields'];
        $customFieldValues      = $options['customFieldValues'];
        $authorizationChecker   = $options['authorizationChecker'];

        $builder->add('firstName', TextType::class, array(
            'label' => 'First name',
            'required' => true,
            'attr' => [
                "autocomplete" => "off"
            ]
        ));

        $builder->add('lastName', TextType::class, array(
            'label' => 'Last name',
            'required' => false,
            'attr' => [
                "autocomplete" => "off"
            ]
        ));

        $builder->add('email', TextType::class, array(
            'label' => 'Email address',
            'required' => false,
            'attr' => [
                "autocomplete" => "off"
            ]
        ));

        $languages = [
            'English'    => 'en',
            'Espanol'    => 'es',
            'Francais'   => 'fr',
            'íslensku'   => 'is',
            'Nederlands' => 'nl',
            'Română'     => 'ro',
            'Slovak'     => 'sk',
            'Svenska'    => 'sv-SE',
            'Welsh'      => 'cy'
        ];
        $builder->add('locale', ChoiceType::class, array(
            'label' => 'Preferred language',
            'choices' => $languages,
            'required' => true,
            'attr' => array(
                'data-help' => 'This member views the member site and receives emails in this language.'
            )
        ));

        if ($options['showSubscriberField']) {
            $label = 'Subscribe to Mailchimp newsletter';
            $help = "We'll add this contact automatically to your Mailchimp list";
        } else {
            $label = 'Receive email newsletters';
            $help = '';
        }
        $builder->add('subscriber', CheckboxType::class, array(
            'required' => false,
            'label' => $label,
            'mapped' => true,
            'attr' => array(
                'data-help' => $help
            )
        ));

        $builder->add('telephone', TextType::class, array(
            'label' => 'Telephone',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('membershipNumber', TextType::class, array(
            'label' => 'Membership number',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('addressLine1', TextType::class, array(
            'label' => 'Address',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('addressLine2', TextType::class, array(
            'label' => 'City',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('addressLine3', TextType::class, array(
            'label' => 'State',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('addressLine4', TextType::class, array(
            'label' => 'Postcode',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('countryIsoCode', CountryType::class, array(
            'label' => 'Country',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('latitude', HiddenType::class, array(
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('longitude', HiddenType::class, array(
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('enabled', CheckboxType::class, array(
            'label' => 'Can log in to Lend Engine public site',
            'required' => false,
            'attr' => array(
                "autocomplete" => "off"
            )
        ));

        $builder->add('children', CollectionType::class, array(
            'entry_type' => ChildType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'label' => false
        ));

        foreach ($customFields AS $field) {
            /** @var $field \AppBundle\Entity\ContactField */
            $fieldId = $field->getId();

            if (isset($customFieldValues[$fieldId])) {
                /** @var \AppBundle\Entity\ContactFieldValue $contactFieldValue */
                $contactFieldValue = $customFieldValues[$fieldId];
                $defaultData = $contactFieldValue->getFieldValue();
            } else {
                $defaultData = '';
            }

            if ($field->getRequired() && $contact->getActiveMembership()) {
                $required = ' (required)';
                $fieldIsRequired = true;
            } else {
                $required = '';
                $fieldIsRequired = false;
            }

            if ($field->getType() == 'select' || $field->getType() == 'choice') {
                $choices = $field->getChoices();
                $choiceArray = [];
                foreach ($choices AS $choice) {
                    $choiceArray[$choice->getOptionName()] = $choice->getId();
                }
                $builder->add('fieldValue' . $fieldId, ChoiceType::class, array(
                    'label' => $field->getName().$required,
                    'placeholder' => '',
                    'required' => $fieldIsRequired,
                    'choices' => $choiceArray,
                    'data' => $defaultData,
                    'mapped' => false
                ));
            } else if ($field->getType() == 'multiselect') {
                $choices = $field->getChoices();
                $choiceArray = [];
                foreach ($choices AS $choice) {
                    $choiceArray[$choice->getOptionName()] = $choice->getId();
                }
                $defaultData = explode(',', $defaultData);
                $builder->add('fieldValue' . $fieldId, ChoiceType::class, array(
                    'label' => $field->getName().$required,
                    'required' => $fieldIsRequired,
                    'choices' => $choiceArray,
                    'data' => $defaultData,
                    'mapped' => false,
                    'multiple' => true
                ));
            } else if ($field->getType() == 'checkbox') {
                if (!$defaultData) {
                    $defaultData = false;
                } else {
                    $defaultData = true;
                }
                $builder->add('fieldValue'.$fieldId, CheckboxType::class, array(
                    'label' => $field->getName().$required,
                    'required' => $fieldIsRequired,
                    'data' => $defaultData,
                    'mapped' => false
                ));
            } else if ($field->getType() == 'text') {
                $builder->add('fieldValue'.$fieldId, TextType::class, array(
                    'label' => $field->getName().$required,
                    'required' => $fieldIsRequired,
                    'data' => $defaultData,
                    'mapped' => false
                ));
            } else if ($field->getType() == 'textarea') {
                $builder->add('fieldValue'.$fieldId, TextareaType::class, array(
                    'label' => $field->getName().$required,
                    'required' => $fieldIsRequired,
                    'data' => $defaultData,
                    'mapped' => false
                ));
            }

        }

        if (!$contact->getId()) {
            $builder->add('sendWelcomeEmail', CheckboxType::class, array(
                'label' => 'Send a welcome email with login details',
                'mapped' => false,
                'required' => false,
                'data' => true
            ));
        } else {
            $builder->add('autoPassword', CheckboxType::class, array(
                'label' => 'Send a new password by email',
                'mapped' => false,
                'required' => false
            ));
        }

        if ( $authorizationChecker->isGranted('ROLE_SUPER_USER') ) {
            $builder->add('roles', ChoiceType::class, array(
                'choices' => array(
                    'Staff (log in to admin)' => 'ROLE_ADMIN',
                    'Administrator (manage account settings)' => 'ROLE_SUPER_USER'
                ),
                'label' => 'Access permissions',
                'expanded' => true,
                'multiple' => true,
                'mapped' => true,
                'attr' => [
                    'data-help' => 'All users can log in and create reservations, if you allow online reservations.'
                ]
            ));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Contact',
            'customFields' => null,
            'customFieldValues' => null,
            'authorizationChecker' => null,
            'showSubscriberField' => null,
            'validation_groups' => ['AppBundleContactEdit']
        ));
    }
}