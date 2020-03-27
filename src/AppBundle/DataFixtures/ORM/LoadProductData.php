<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Site;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\InventoryItem;

class LoadProductData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {

        $site = new Site();
        $site->setName("Second site");
        $site->setCountry("GB");
        $site->setAddress("...");
        $site->setPostCode("...");
        $site->setIsActive(true);
        $manager->persist($site);

        $inStock = new InventoryLocation();
        $inStock->setName("In stock");
        $inStock->setIsAvailable(true);
        $inStock->setIsActive(true);
        $inStock->setSite($site);
        $manager->persist($inStock);

        $site->setDefaultCheckInLocation($inStock);

        $inventoryLocation = new InventoryLocation();
        $inventoryLocation->setName("Repair");
        $inventoryLocation->setIsAvailable(false);
        $inventoryLocation->setIsActive(true);
        $inventoryLocation->setSite($site);
        $manager->persist($inventoryLocation);

        // New test item will be ID 1000
        $item = new InventoryItem();
        $item->setName("Test item");
        $item->setInventoryLocation($inStock);
        $manager->persist($item);

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryLocation($inStock);
        $transactionRow->setInventoryItem($item);
        $manager->persist($transactionRow);

        // New stock item will be ID 1001
        $item = new InventoryItem();
        $item->setName("Test stock item");
        $item->setItemType(InventoryItem::TYPE_STOCK);
        $manager->persist($item);

        $manager->flush();
    }
}