<?php

namespace AppBundle\Services\Loan;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
// +++ KB-MAN 2024/02/25
use AppBundle\Entity\Tenant;
// --- KB-MAN 2024/02/25
use AppBundle\Services\SettingsService;
use AppBundle\Services\TenantService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class LoanService
{
    /** @var EntityManager  */
    private $em;

    /** @var SettingsService  */
    private $settings;

    /**
     * @var array
     */
    public $errors = [];

    public function __construct(EntityManager $em,
                                SettingsService $settings)
    {
        $this->em        = $em;
        $this->settings  = $settings;
    }

    // +++ KB-MAN 2024/02/25 Allow update of tenant / entity manager for use in schedule/CleanUpClosedLoans (cfr ContactService, SettingsService)
    /**
     * Override the active tenant (used in a scheduled loop eg cleanup closed loans)
     * @param Tenant $tenant
     * @param EntityManager $em
     */
    public function setTenant(Tenant $tenant, EntityManager $em = null)
    {
        $this->tenant = $tenant;
        if ($em) {
            $this->em = $em;
            $this->db = $this->em->getConnection()->getDatabase();
        }
    }
    // --- KB-MAN 2024/02/25
    
    /**
     * @param $loanId
     * @return \AppBundle\Entity\Loan|null
     */
    public function get($loanId)
    {
        $repo = $this->em->getRepository('AppBundle:Loan');
        return $repo->find($loanId);
    }

    /**
     * @param $id int
     * @return bool
     */
    public function deleteLoan($id)
    {
        $repo = $this->em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($id)) {
            $this->errors[] = "Could not find loan with ID ".$id;
            return false;
        }

        if ($loan->getStatus() == Loan::STATUS_ACTIVE) {
            $this->errors[] = "You can't delete active loans : {$id}.";
            return false;
        }

        if ($loan->getStatus() == Loan::STATUS_OVERDUE) {
            $this->errors[] = "You can't delete overdue loans : {$id}.";
            return false;
        }

        // Get all item movements associated with the loan (for closed loans)
        /** @var \AppBundle\Repository\ItemMovementRepository $itemMovementRepo */
        $itemMovementRepo = $this->em->getRepository('AppBundle:ItemMovement');

        /** @var \AppBundle\Repository\DepositRepository $depositRepo */
        $depositRepo = $this->em->getRepository('AppBundle:Deposit');

        /** @var \AppBundle\Entity\LoanRow $row */
        foreach ($loan->getLoanRows() AS $row) {

            $itemMovements = $itemMovementRepo->findBy(['loanRow' => $row->getId()]);
            foreach ($itemMovements AS $movement) {
                $this->em->remove($movement);
            }

            $deposits = $depositRepo->findBy(['loanRow' => $row->getId()]);

            /** @var \AppBundle\Entity\Deposit $deposit */
            foreach ($deposits AS $deposit) {
                if ($deposit->getBalance() == 0) {

//                    $row->setDeposit(null);
//                    $this->em->persist($row);
//                    $deposit->setLoanRow(null);
//                    $this->em->persist($deposit);
//                    $this->em->flush();
//
//                    $this->em->remove($deposit);
                } else {
//                    $this->errors[] = "Loan has un-refunded deposit : {$id}";
//                    return false;
                }
                $this->errors[] = "Loan has a deposit : {$id}";
                return false;
            }

        }

        $this->em->remove($loan);

        try {
            $this->em->flush();
        } catch(\Exception $generalException) {
            $this->errors[] = 'Loan failed to delete.';
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param Loan $loan
     * @param $user
     * @param $amount
     * @param $text
     * @return bool
     */
    public function addFee(Loan $loan, $user, $amount, $text)
    {
        $iso = $this->settings->getSettingValue('org_currency');
        $currencySymbol = \Symfony\Component\Intl\Intl::getCurrencyBundle()->getCurrencySymbol($iso);

        $payment = new Payment();
        $payment->setCreatedBy($user);
        $payment->setCreatedAt(new \DateTime());
        $payment->setLoan($loan);
        $payment->setNote($text);
        $payment->setContact($loan->getContact());
        $payment->setAmount(-$amount);
        $this->em->persist($payment);

        // Add audit trail
        $note = new Note();
        $note->setCreatedBy($user);
        $note->setCreatedAt(new \DateTime());
        $note->setContact($loan->getContact());
        $note->setLoan($loan);
        $note->setText("Added fee of {$currencySymbol}" . $amount . "; {$text}");
        $this->em->persist($note);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param string $status
     * @param \DateTime $dateTo
     * @return int
     */
    public function countLoans($status = '', \DateTime $dateTo = null)
    {
        $repository = $this->em->getRepository('AppBundle:Loan');
        $builder = $repository->createQueryBuilder('l');
        $builder->add('select', 'COUNT(l) AS qty');
        if ($status) {
            $builder->andWhere('l.status LIKE :status');
            $builder->setParameter('status', '%'.$status.'%');
        }
        if ($dateTo) {
            $builder->andWhere("l.createdAt < :dateTo");
            $builder->setParameter('dateTo', $dateTo->format("Y-m-01"));
        }
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = $results[0]['qty'];
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @param $filter
     * @return int
     */
    public function countLoanRows($filter)
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');
        $builder = $repository->createQueryBuilder('lr');
        $builder->join('lr.loan', 'l');
        $builder->add('select', 'COUNT(lr) AS qty');
        if (isset($filter['status']) && $filter['status']) {
            $builder->andWhere('l.status LIKE :status');
            $builder->setParameter('status', '%'.$filter['status'].'%');
        }
        if (isset($filter['contact']) && $filter['contact']) {
            $builder->andWhere('l.contact = :contact');
            $builder->setParameter('contact', $filter['contact']);
        }
        if (isset($filter['isOnLoan']) && $filter['isOnLoan']) {
            $builder->andWhere('lr.checkedOutAt IS NOT NULL AND lr.checkedInAt IS NULL');
        }
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = $results[0]['qty'];
        } else {
            $total = 0;
        }

        return $total;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function loansAddedByMonth()
    {
        $sql = "SELECT DATE(l.created_at) AS d,
                  count(*) AS c
                  FROM loan l
                  GROUP BY DATE(l.created_at)";

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
            $data[$key] += $result['c'];
        }
        return $data;
    }

}