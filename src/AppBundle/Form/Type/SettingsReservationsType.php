<?php
// src/AppBundle/Form/Type/SettingsReservationsType.php
namespace AppBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

use AppBundle\Form\Type\ToggleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsReservationsType extends AbstractType
{
    /** @var EntityManager */
    public $em;

    function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];

        // Get the settings
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');
        $dbData = $repo->getAllSettings();

        // LOANS

        /** @var $repo \AppBundle\Repository\InventoryLocationRepository */
        $repo =  $this->em->getRepository('AppBundle:InventoryLocation');
        $location = $repo->find($dbData['default_checkin_location']);
        $builder->add('default_checkin_location', EntityType::class, array(
            'label' => 'Default check in location',
            'class' => 'AppBundle:InventoryLocation',
            'choice_label' => 'name',
            'required' => true,
            'data' => $location,
            'attr' => array(
                'data-help' => "When checking in a loan, we'll set this for you to save time. You can change it when you check in.",
            )
        ));

        $builder->add('default_loan_fee', TextType::class, array(
            'label' => 'Loan fee',
            'data' => $dbData['default_loan_fee'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => "If an item has no loan fee, this amount will be used on the loan."
            )
        ));

        $builder->add('default_loan_days', TextType::class, array(
            'label' => 'Your normal loan period (days)',
            'data' => $dbData['default_loan_days'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'This can be overridden for each item. <br><strong style="color: #ff7b1c">Loan periods need to align with your opening days</strong>'
            )
        ));

        $builder->add('min_loan_days', TextType::class, array(
            'label' => 'Minimum loan period for self-serve reservations or loans (days)',
            'data' => $dbData['min_loan_days'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'Leave blank for no limit. Administrators are not restricted to a maximum loan period.'
            )
        ));

        $builder->add('max_loan_days', TextType::class, array(
            'label' => 'Maximum loan period for self-serve reservations or loans (days)',
            'data' => $dbData['max_loan_days'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'Leave blank for no limit. Administrators are not restricted to a maximum loan period.'
            )
        ));

        $helpText = <<<EOH
Most libraries choose a fee per day or week, but if you want to choose a fixed fee per borrow, select YES here.
We'll ignore the number of days in the setting above.
EOH;

        $builder->add('fixed_fee_pricing', ToggleType::class, array(
            'expanded' => true,
            'multiple' => false,
            'label' => 'OR ... prices are fixed per borrow',
            'data' => (int)$dbData['fixed_fee_pricing'],
            'required' => true,
            'attr' => [
                'class' => 'input-100 toggle-switch',
                'data-help' => $helpText
            ]
        ));

        $builder->add('daily_overdue_fee', TextType::class, array(
            'label' => 'Late return fee (per day)',
            'data' => $dbData['daily_overdue_fee'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => 'Used for the fee field when checking in late items.'
            )
        ));

        $builder->add('loan_terms', TextareaType::class, array(
            'label' => 'Terms and conditions to send to members upon checkout',
            'data' => $dbData['loan_terms'],
            'required' => false,
            'attr' => array(
                'placeholder' => 'HTML or text terms and conditions',
                'rows' => 4,
                'class' => 'limited',
                'data-help' => 'This text will be added to outgoing loan and reservation confirmation emails. A mandatory checkbox will also be added to the checkout process.'
            )
        ));

        // RESERVATIONS

        $builder->add('reservation_fee', TextType::class, array(
            'label' => 'Reservation fee to charge when reservation is placed',
            'data' => number_format((float)$dbData['reservation_fee'], 2),
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => "A fixed fee charged to a member when a reservation is created"
            )
        ));

        $choices = [
            'When the reservation is placed' => 1,
            'When the reservation is checked out' => 0
        ];
        $builder->add('charge_daily_fee', ChoiceType::class, array(
            'choices' => $choices,
            'label' => 'When do you want to add the item fees to the member account?',
            'data' => (int)$dbData['charge_daily_fee'],
            'required' => true,
            'attr' => [
                'class' => '',
                'data-help' => ''
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