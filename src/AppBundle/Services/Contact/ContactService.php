<?php

namespace AppBundle\Services\Contact;

use AppBundle\Entity\Contact;
use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Tenant;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class ContactService
{

    /** @var EntityManager  */
    private $em;

    /** @var Container  */
    private $container;

    /** @var array  */
    public $errors = [];

    /** @var \AppBundle\Repository\ContactRepository */
    private $repository;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em         = $em;
        $this->container  = $container;
        $this->repository = $this->em->getRepository('AppBundle:Contact');
    }

    /**
     * @param $id
     * @return \AppBundle\Entity\Contact
     */
    public function get($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param Contact $contact
     * @return mixed|string
     */
    public function generateAccessToken(Contact $contact)
    {
        if ($contact->getSecureAccessToken()) {
            return $contact->getSecureAccessToken();
        }
        $token = uniqid();
        $contact->setSecureAccessToken($token);
        $this->em->persist($contact);
        $this->em->flush($contact);
        return $token;
    }

    /**
     * Override the active tenant (used in a scheduled loop eg reminders)
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

    /**
     * This function gets the data required to feed the contact list DataTable via AJAX
     * @param $start
     * @param $length
     * @param $filter
     * @param $sort
     *
     * @return array
     */
    public function contactSearch($start, $length, $filter, $sort = [])
    {
        $builder = $this->repository->createQueryBuilder('c');

        $builder->leftJoin('c.memberships', 'm');
        $builder->select('c');
        $builder->where('c.id > 1');
        $builder->andWhere('c.isActive = 1');

        if (isset($filter['search']) && is_numeric($filter['search'])) {
            // Searching by member number
            $builder->andWhere("c.membershipNumber = :string");
            $builder->setParameter('string', trim($filter['search']));
        } else if (isset($filter['search']) && $filter['search']) {
            // Searching by text
            $builder->andWhere("c.firstName LIKE :likeString
                OR c.lastName LIKE :likeString
                OR c.email LIKE :likeString
                OR CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) LIKE :likeString
                ");
            $builder->setParameter('likeString', '%'.trim($filter['search']).'%');
        }

        if (isset($filter['hasMembership']) && $filter['hasMembership'] == 1) {
            $builder->andWhere('m.expiresAt > :date');
            $builder->andWhere('m.status != :statusCancelled');
            $builder->setParameter('date', date("Y-m-d H:i:s"));
            $builder->setParameter('statusCancelled', Membership::SUBS_STATUS_CANCELLED);
        }

        if (isset($filter['membershipType']) && $filter['membershipType']) {
            $builder->andWhere('m.membershipType = '.(int)$filter['membershipType']);
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            $builder->andWhere('c.createdAt >= :date_from');
            $builder->setParameter('date_from', $filter['date_from']);
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('c.createdAt <= :date_to');
            $builder->setParameter('date_to', $filter['date_to'].' 23:59:59');
        }

        // Run without pages to get total results:
        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add pages:
        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:
        if (is_array($sort) && count($sort) > 0 && $this->validateSort($sort)) {
            $builder->addOrderBy("c.".$sort['column'], $sort['direction']);
        } else {
            $builder->addOrderBy("c.firstName");
        }

        // Get the data:
        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];

    }

    /**
     * @param \DateTime $dateTo
     * @return int
     */
    public function countAllContacts(\DateTime $dateTo = null)
    {
        $builder = $this->repository->createQueryBuilder('c');
        $builder->add('select', 'COUNT(c) AS qty');
        $builder->where('c.id > 1');
        if ($dateTo) {
            $builder->andWhere("c.createdAt < :dateTo");
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
     * @return array
     * @throws DBALException
     */
    public function contactsAddedByMonth()
    {
        $sql = "SELECT DATE(c.created_at) AS d,
                  count(*) AS c
                  FROM contact c
                  WHERE c.id > 1
                  GROUP BY DATE(c.created_at)";

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

    /**
     * @param Contact $contact
     * @return bool
     */
    public function recalculateBalance(Contact $contact)
    {
        $builder = $this->em->getRepository('AppBundle:Payment')->createQueryBuilder('p');

        $builder->add('select', "SUM(CASE WHEN p.type = 'PAYMENT' THEN p.amount ELSE -p.amount END) AS balance");
        $builder->where('p.contact = :contact');

        $builder->andWhere("p.deposit IS NULL");

        $builder->setParameter('contact', $contact->getId());
        $query = $builder->getQuery();
        $results = $query->getResult();
        $balance = $results[0]['balance'];

        $contact->setBalance($balance);
        $this->em->persist($contact);

        try {
            $this->em->flush($contact);
        } catch (\Exception $generalException) {

        }

        return true;
    }

    /**
     * @param Contact $contact
     * @return Contact
     */
    public function loadCustomerCards(Contact $contact) {
        // Get existing cards for a customer
        $stripeUseSavedCards = $this->container->get('settings')->getSettingValue('stripe_use_saved_cards');

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->container->get('service.stripe');

        $customerStripeId = $contact->getStripeCustomerId();
        if ($customerStripeId && $stripeUseSavedCards) {
            // Retrieve their cards
            $paymentMethods = $stripeService->getCustomerPaymentMethods($customerStripeId);
            if (isset($paymentMethods['data'])) {
                foreach($paymentMethods['data'] AS $source) {
                    $creditCard = new CreditCard();
                    $creditCard->setLast4($source['card']['last4']);
                    $creditCard->setExpMonth($source['card']['exp_month']);
                    $creditCard->setExpYear($source['card']['exp_year']);
                    $creditCard->setBrand($source['card']['brand']);
                    $creditCard->setCardId($source['id']);
                    $contact->addCreditCard($creditCard);
                }
            }
        }

        return $contact;
    }

    /**
     * @param array $sort
     * @return bool
     */
    private function validateSort($sort = [])
    {
        return true;
    }

}