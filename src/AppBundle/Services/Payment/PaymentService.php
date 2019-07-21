<?php

namespace AppBundle\Services\Payment;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Deposit;
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

class PaymentService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SettingsService
     */
    private $settings;

    /**
     * @var StripeHandler
     */
    private $stripeService;

    /**
     * @var PaymentRepository
     */
    private $repo;

    /**
     * @var array
     */
    public $errors = [];

    public function __construct(EntityManager $em, SettingsService $settings, StripeHandler $stripeService)
    {
        $this->em        = $em;
        $this->settings  = $settings;
        $this->stripeService = $stripeService;
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
     * @param null $cardDetails
     * @return Payment
     * @throws \Exception
     */
    public function create(Payment $p, $cardDetails = null)
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
        if ($p->getType() == Payment::PAYMENT_TYPE_DEPOSIT) {
            $feeAmount = 0;
        }

        // If Stripe payment is required first, go off and do that
//        $stripePaymentMethodId = $this->settings->getSettingValue('stripe_payment_method');
//
//        if ($p->getPaymentMethod() && $stripePaymentMethodId == $p->getPaymentMethod()->getId()) {
//
//            if ($feeAmount > 0) {
//                // Increase the amount of this payment
//                $p->setAmount($feeAmount + $basePaymentAmount);
//                // Add a fee to reduce the customer balance by the fee amount
//                $fee = new Payment();
//                $fee->setCreatedBy($p->getCreatedBy());
//                $fee->setAmount(-$feeAmount);
//                $fee->setContact($p->getContact());
//                $fee->setLoan($p->getLoan());
//                $fee->setNote("Card fee.");
//                $this->em->persist($fee);
//            }
//
//            if (!isset($cardDetails['token'])) {
//                $cardDetails['token'] = null;
//            }
//            if (!isset($cardDetails['cardId'])) {
//                $cardDetails['cardId'] = null;
//            }
//
//            $token  = $cardDetails['token'];
//            $cardId = $cardDetails['cardId'];
//
//            if (!$token && !$cardId) {
//                $this->errors[] = "A card ID or token is required to process a payment with Stripe";
//            }
//
//            if ($p->getLoan()) {
//                $note = 'Loan '.$p->getLoan()->getId();
//            } else {
//                $note = 'Payment taken via Lend Engine';
//            }
//            if ($charge = $this->stripeService->processPayment($token, $cardId, $p, $note)) {
//                // $p will be persisted later
//                if (isset($charge->id)) {
//                    $p->setPspCode($charge->id);
//                }
//            } else {
//                $this->errors[] = 'Payment service failed to get a successful payment from Stripe for "'.$p->getNote().'" ';
//                foreach ($this->stripeService->errors AS $error) {
//                    $this->errors[] = $error;
//                }
//                return false;
//            }
//        }

        // Create a deposit entity as well as a payment
        if ($p->getType() == Payment::PAYMENT_TYPE_DEPOSIT) {

            $deposit = new Deposit();
            $deposit->setCreatedBy($p->getCreatedBy());
            $deposit->setContact($p->getContact());
            $deposit->setAmount($basePaymentAmount); // Exclude any Stripe payment fee

            if (!$p->getLoanRow()) {
                throw new \Exception("A loanRow is required when creating a deposit payment");
            }
            $deposit->setLoanRow($p->getLoanRow());

            // Link payments, deposits and loan row for a transactional write
            $p->getLoanRow()->setDeposit($deposit);
            $p->setDeposit($deposit);

            $this->em->persist($deposit);

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

        return $p;

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

}