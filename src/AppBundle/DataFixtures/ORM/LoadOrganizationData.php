<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\PaymentMethod;
use AppBundle\Entity\SiteOpening;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Setting;

class LoadOrganizationData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Sample data
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setName("Stripe");
        $manager->persist($paymentMethod);
        $manager->flush();

//        $setting = new Setting();
//        $setting->setSetupKey('stripe_access_token')->setSetupValue('test');
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_refresh_token')->setSetupValue('test');
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_user_id')->setSetupValue('acct_17zrLaDuX5OG9FwD');
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_publishable_key')->setSetupValue('test');
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_payment_method')->setSetupValue($paymentMethod->getId());
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_minimum_payment')->setSetupValue('5');
//        $manager->persist($setting);
//
//        $setting = new Setting();
//        $setting->setSetupKey('stripe_fee')->setSetupValue('1');
//        $manager->persist($setting);

        // Create some site openings
        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $manager->getRepository('AppBundle:Site');
        $site = $siteRepo->find(1);

        for ($n=1; $n<8; $n++) {
            $siteOpening = new SiteOpening();
            $siteOpening->setSite($site);
            $siteOpening->setWeekDay($n);
            $siteOpening->setTimeFrom('0900');
            $siteOpening->setTimeTo('1700');
            $manager->persist($siteOpening);
        }

        $manager->flush();

    }
}