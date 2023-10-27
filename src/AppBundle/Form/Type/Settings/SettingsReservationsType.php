<?php
// src/AppBundle/Form/Type/SettingsReservationsType.php
namespace AppBundle\Form\Type\Settings;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Type\ToggleType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingsReservationsType extends AbstractType
{
    /** @var EntityManager */
    public $em;

    /** @var \AppBundle\Services\SettingsService */
    public $settingsService;

    function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $this->em = $options['em'];
        $this->settingsService = $options['settingsService'];

        // Get the settings
        $dbData = $this->settingsService->getAllSettingValues();

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

        $builder->add('reservation_buffer', TextType::class, array(
            'label' => 'Buffer hours between loans',
            'data' => $dbData['reservation_buffer'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => "Use this to add a buffer period between loans for cleaning, inspection or quarantine."
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

        $helpText = <<<EOH
<strong>On the item page</strong>: Auto-add items to an existing basket when clicking "borrow" if there are no date conflicts. 
For the first item added to a basket, the user needs to choose dates.<br>
<strong>On the item listing</strong>: Adds an "add to basket" button which adds to the basket in the background, if 
a basket exists and there is no clash with dates, locations or membership restrictions.
EOH;

        $builder->add('basket_quick_add', ToggleType::class, array(
            'expanded' => true,
            'multiple' => false,
            'label' => 'Quick add basket mode (beta)',
            'data' => (int)$dbData['basket_quick_add'],
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

        $builder->add('max_reservations', TextType::class, array(
            'label' => 'Maximum reservations per member',
            'data' => $dbData['max_reservations'],
            'required' => false,
            'attr' => array(
                'placeholder' => '',
                'class' => 'input-100',
                'data-help' => "How many reservations a member can have at any one time. Applies to members logged in only; no limit when you are admin. Set to 0 to turn off the reservation feature for members."
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

        $builder->add('forward_picking', ToggleType::class, array(
            'expanded' => true,
            'multiple' => false,
            'label' => 'Enable reservation forward picking locations at sites. Setup location and edit site to include a forward picking location',
            'data' => (int)$dbData['forward_picking'],
            'required' => true,
            'disabled'=>true,
            'attr' => [
                'class' => 'input-100 toggle-switch',
                'disabled'=>true
            ]
        ));

        // POSTAL LOANS

        $builder->add('postal_loans', ToggleType::class, array(
            'expanded' => true,
            'multiple' => false,
            'label' => 'Allow postal loans',
            'data' => (int)$dbData['postal_loans'],
            'required' => true,
            'attr' => [
                'class' => 'input-100 toggle-switch',
                'data-help' => 'Turn this on to show a button on loans for "send loan by post".'
            ]
        ));

        $builder->add('postal_item_fee', TextType::class, array(
            'label' => 'Fee per item',
            'data' => number_format((float)$dbData['postal_item_fee'], 2),
            'required' => false,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "Add this amount to the shipping fee, once for each item on the loan."
            ]
        ));

        $builder->add('postal_loan_fee', TextType::class, array(
            'label' => 'Fee per loan',
            'data' => number_format((float)$dbData['postal_loan_fee'], 2),
            'required' => false,
            'attr' => [
                'class' => 'input-100',
                'data-help' => "Add this amount as a fee, once per loan."
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
            'settingsService' => null
        ));
    }
}