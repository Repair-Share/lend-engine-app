<?php

namespace AppBundle\Services\Payment;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Deposit;
use AppBundle\Entity\PaymentMethod;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\Debug\DebugService;
use AppBundle\Services\StripeHandler;
use AppBundle\Services\SettingsService;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use AppBundle\Repository\PaymentRepository;

/**
 * Class PaymentService
 * @package AppBundle\Services\Payment
 *
 *
 * Payment scenarios:
 * Add credit via admin
 * Add credit via member site
 * Check out loan
 * Check out loan with deposit items, when item has fee
 * Check out loan with deposit items, when item is FREE
 * Create reservation with reservation fee
 * Add credit with card payment fee
 * Extend a loan and charge a fee
 * Create and pay for membership
 * Book onto event and pay via member site
 * Take payment for event booking via admin
 *
 */

class PaymentService
{

    /** @var EntityManager  */
    private $em;

    /** @var SettingsService  */
    private $settings;

    /** @var StripeHandler  */
    private $stripeService;

    /** @var ContactService */
    private $contactService;

    /** @var DebugService */
    private $debugService;

    /** @var PaymentRepository */
    private $repo;

    /** @var array */
    public $errors = [];

    public function __construct(EntityManager $em,
                                SettingsService $settings,
                                StripeHandler $stripeService,
                                ContactService $contactService,
                                DebugService $debugService)
    {
        $this->em        = $em;
        $this->settings  = $settings;
        $this->stripeService = $stripeService;
        $this->contactService = $contactService;
        $this->debugService = $debugService;
        $this->repo = $em->getRepository('AppBundle:Payment');
    }

    /**
     * @param $filters
     * @return array|bool
     */
    public function get($filters)
    {
        if ($payments = $this->repo->findBy($filters)) {
            return $payments;
        }

        return false;
    }

    /**
     * @param Payment $p
     * @return Payment
     * @throws \Exception
     */
    public function create(Payment $p)
    {
        // Validate
        if (!$p->getContact()) {
            throw new \Exception("A Contact is required when creating a payment");
        }

        if ($p->getType() == Payment::PAYMENT_TYPE_REFUND) {
            throw new \Exception("System error : trying to do a refund with the create-payment service");
        }

        $basePaymentAmount = $p->getAmount();
        $feeAmount = (float)$this->settings->getSettingValue('stripe_fee');

        // Don't add card fee to deposits
        // If a deposit is being taken as part of a loan payment, then the card fee will be added to the loan payment portion
        // Deposits cannot be taken any other way   ??
        if ($p->getType() == Payment::PAYMENT_TYPE_DEPOSIT) {
            $feeAmount = 0;
        }

        $this->debugService->stripeDebug(
            'Create payment service',
            $p->getDebug()
        );

        $stripePaymentMethodId = $this->settings->getSettingValue('stripe_payment_method');

        if ($p->getPaymentMethod() && $stripePaymentMethodId == $p->getPaymentMethod()->getId()) {

            $this->debugService->stripeDebug('Stripe payment detected');

            if ($feeAmount > 0) {
                // Increase the amount of this payment
                $p->setAmount($feeAmount + $basePaymentAmount);
                // Add a fee to reduce the customer balance by the fee amount
                $fee = new Payment();
                $fee->setCreatedBy($p->getCreatedBy());
                $fee->setAmount(-$feeAmount);
                $fee->setContact($p->getContact());
                $fee->setLoan($p->getLoan());
                $fee->setNote("Card fee.");
                $this->em->persist($fee);

                $this->debugService->stripeDebug('Increased the amount of this payment', $p->getDebug());
                $this->debugService->stripeDebug('Added a fee to reduce the customer balance by the fee amount', $fee->getDebug());

            }
        }

        // Create a deposit entity as well as a payment
        if ($p->getType() == Payment::PAYMENT_TYPE_DEPOSIT) {

            $deposit = new Deposit();
            $deposit->setCreatedBy($p->getCreatedBy());
            $deposit->setContact($p->getContact());
            $deposit->setAmount($basePaymentAmount); // Exclude any Stripe payment fee

            $this->debugService->stripeDebug('Created a deposit entity as well as a payment', $deposit->getDebug());

            if (!$p->getLoanRow()) {
                throw new \Exception("A loanRow is required when creating a deposit payment");
            }
            $deposit->setLoanRow($p->getLoanRow());

            // Link payments, deposits and loan row for a transactional write
            $p->getLoanRow()->setDeposit($deposit);
            $p->setDeposit($deposit);

            $this->em->persist($deposit);

            $this->debugService->stripeDebug(
                'Linked payments, deposits and loan row for a transactional write',
                $p->getDebug()
            );

        }

        $this->em->persist($p);

        // Do all the saving to DB
        if (isset($deposit)) {
            $this->em->flush($deposit);
        }
        if (isset($fee)) {
            $this->em->flush($fee);
        }
        $this->em->flush($p);

        $this->debugService->stripeDebug(
            $this->debugService->getSeparator()
        );

        return $p;

    }

    /**
     * @param $contactId
     * @param $paymentMethod
     * @return bool
     */
    public function saveCard($contactId, $paymentMethod)
    {
        $contact = $this->contactService->get($contactId);

        if ($stripeCustomerId = $contact->getStripeCustomerId()) {
            // We already have a customer in Stripe, add this card
            if (!$this->stripeService->attachPaymentMethod($stripeCustomerId, $paymentMethod)) {
                // @todo pass the [non-fatal] errors up to the user
            }
        }

        return true;
    }
    /**
     * @param $id
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     */
    public function deletePayment($id)
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create(
                $this->em->getConnection(),
                $this->em->getConfiguration()
            );
        }

        /** @var \AppBundle\Entity\Payment $payment */
        if (!$payment = $this->repo->find($id)) {
            $this->errors[] = "Could not find payment with ID ".$id;
            return false;
        }

        $this->em->remove($payment);

        try {
            $this->em->flush();
        } catch(\Exception $generalException) {
            $this->errors[] = 'Payment failed to delete.';
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param $chargeId string
     * @param Payment $p
     * @return Payment|bool
     * @throws \Exception
     */
    public function refund(Payment $p, $chargeId = null)
    {
        if ($p->getType() != Payment::PAYMENT_TYPE_REFUND) {
            throw new \Exception("System error : not a refund via the refund service");
        }

        if (!$p->getAmount()) {
            $this->errors[] = "No amount set to refund";
            return false;
        }

        $stripePaymentMethodId = $this->settings->getSettingValue('stripe_payment_method');
        if ($stripePaymentMethodId == $p->getPaymentMethod()->getId()) {
            if (!$chargeId) {
                $this->errors[] = "You can't refund this transaction via Stripe : no payment ID found";
            }
            // Perform the Stripe refund
            if ($charge = $this->stripeService->refundPayment($chargeId, $p->getAmount())) {
                $p->setPspCode($charge->id);
            } else {
                foreach ($this->stripeService->errors AS $error) {
                    $this->errors[] = $error;
                }
                return false;
            }
        }

        // Update the deposit with the total amount refunded so far
        if ($deposit = $p->getDeposit()) {
            $balance = $deposit->getBalance();
            $balance -= $p->getAmount();
            $deposit->setBalance($balance);
            $this->em->persist($deposit);
            $this->em->flush($deposit);
        }

        // Save the refund as a payment
        $this->em->persist($p);
        $this->em->flush($p);

        return $p;
    }

    /**
     * @param null $feeType
     * @return array
     * @throws DBALException
     */
    public function paymentsByMonth($feeType = null)
    {

        $extraFilter = '';
        if ($feeType == 'memberships') {
            $extraFilter = "AND membership_id > 0 ";
        } else if ($feeType == 'events') {
            $extraFilter = "AND event_id > 0 ";
        } else if ($feeType == 'other') {
            $extraFilter = "AND membership_id IS NULL AND event_id IS NULL";
        }

        $sql = "SELECT DATE(p.created_at) AS d,
                  SUM(amount) AS fee
                  FROM payment p
                  WHERE type = 'FEE'
                  {$extraFilter}
                  GROUP BY DATE(p.created_at)";

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        // key by "Y-m"
        $data = [];
        foreach ($results AS $result) {
            $key = substr($result['d'], 0, 7);
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] += $result['fee'];
        }
        return $data;
    }

    /**
     * @param $paymentMethodId
     * @return bool|null|PaymentMethod
     */
    public function getPaymentMethodById($paymentMethodId)
    {
        $paymentMethodRepository = $this->em->getRepository("AppBundle:PaymentMethod");
        if ($paymentMethod = $paymentMethodRepository->find($paymentMethodId)) {
            return $paymentMethod;
        } else {
            return false;
        }
    }

}