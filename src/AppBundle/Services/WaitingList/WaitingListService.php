<?php

namespace AppBundle\Services\WaitingList;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\WaitingListItem;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Postmark\PostmarkClient;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class WaitingListService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var array
     */
    public $errors = [];

    /**
     * @param EntityManager $em
     * @param Container $container
     * @param \Twig_Environment $twig
     */
    public function __construct(EntityManager $em, Container $container, \Twig_Environment $twig)
    {
        $this->em = $em;
        $this->container = $container;
        $this->twig = $twig;
    }

    /**
     * @param InventoryItem $inventoryItem
     */
    public function process(InventoryItem $inventoryItem)
    {
        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->em->getRepository('AppBundle:WaitingListItem');

        $filter = [
            'inventoryItem' => $inventoryItem,
            'removedAt' => null
        ];
        $waitingListItems = $waitingListRepo->findBy($filter);

        foreach ($waitingListItems AS $waitingListItem) {
            /** @var $waitingListItem \AppBundle\Entity\WaitingListItem */
            $waitingListItem->setRemovedAt(new \DateTime());
            $this->em->persist($waitingListItem);
            $this->sendEmail($waitingListItem->getInventoryItem(), $waitingListItem->getContact());
        }

        try {
            $this->em->flush();
        } catch (\Exception $generalException) {

        }
    }

    /**
     * @param InventoryItem $item
     * @param Contact $contact
     * @return bool
     */
    private function sendEmail(InventoryItem $item, Contact $contact)
    {
        $postMarkApiKey = getenv('SYMFONY__POSTMARK_API_KEY');

        $tenantCompany = '';

        try {
            $toEmail = $contact->getEmail();
            $client = new PostmarkClient($postMarkApiKey);
            $message = $this->twig->render(
                'emails/item_waiting_list_alert.html.twig',
                array(
                    'item' => $item,
                    'user_locale' => $contact->getLocale()
                )
            );
            $client->sendEmail(
                "{$tenantCompany} <hello@lend-engine.com>",
                $toEmail,
                "Item is now available",
                $message
            );

            return true;
        } catch (\Exception $generalException) {
            $this->errors[] = "ERROR: Failed to send email : " . PHP_EOL . $generalException->getMessage();

            return false;
        }
    }

    /**
     * Add an item to the waiting list
     * @param Contact $contact
     * @param InventoryItem $item
     * @return bool
     */
    public function add(Contact $contact, InventoryItem $item)
    {
        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->em->getRepository('AppBundle:WaitingListItem');

        $filter = [
            'contact' => $contact,
            'removedAt' => null,
            'inventoryItem' => $item
        ];
        if ($waitingListRepo->findOneBy($filter)) {
            $this->errors[] = "You're already on the waiting list for this item.";
        }

        /** @var \AppBundle\Entity\WaitingListItem $waitingListItem */
        $waitingListItem = new WaitingListItem();
        $waitingListItem->setContact($contact);
        $waitingListItem->setInventoryItem($item);

        $this->em->persist($waitingListItem);

        try {
            $this->em->flush();
            return true;
        } catch (\Exception $generalException) {
            $this->errors[] = $generalException->getMessage();
            return false;
        }
    }

    /**
     * @param Contact $contact
     * @param InventoryItem $item
     * @return bool
     */
    public function deleteEntry(Contact $contact, InventoryItem $item)
    {
        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->em->getRepository('AppBundle:WaitingListItem');

        $filter = [
            'contact' => $contact,
            'removedAt' => null,
            'inventoryItem' => $item
        ];

        if ($waitingListEntry = $waitingListRepo->findOneBy($filter)) {
            $this->em->remove($waitingListEntry);

            try {
                $this->em->flush();
                return true;
            } catch (\Exception $generalException) {
                $this->errors[] = $generalException->getMessage();
                return false;
            }
        }

        return false;

    }

}
