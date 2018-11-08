<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Membership;
use AppBundle\Entity\MembershipType;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Contact;

class LoadContactData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // First contact
        $userAdmin = new Contact();
        $userAdmin->setFirstName('Admin');
        $userAdmin->setLastName('Admin');
        $userAdmin->setUsername('hello@lend-engine.com');
        $userAdmin->setEmail('hello@lend-engine.com');
        $userAdmin->setPlainPassword('unit_test');
        $userAdmin->setEnabled(true);
        $userAdmin->addRole("ROLE_ADMIN");
        $userAdmin->addRole("ROLE_SUPER_USER");
        $manager->persist($userAdmin);
        $manager->flush();

        // Second contact as an admin
        $user = new Contact();
        $user->setUsername('test@email.com');
        $user->setEmail('test@email.com');
        $user->setFirstName("John");
        $user->setLastName("Doe");
        $user->setPlainPassword('test');
        $user->setEnabled(true);
        $user->addRole("ROLE_ADMIN");
        $user->addRole("ROLE_SUPER_USER");
        $manager->persist($user);
        $manager->flush();

        // Third contact to create a subscription for
        $contact = new Contact();
        $contact->setUsername('contact@email.com');
        $contact->setEmail('contact@email.com');
        $contact->setFirstName("Emily");
        $contact->setLastName("Edwardson");
        $contact->setPlainPassword('test');
        $contact->setEnabled(true);
        $manager->persist($contact);
        $manager->flush();

        // Default membership data created in migration 0000
        $membershipType = $manager->getRepository('AppBundle:MembershipType')->find(1);

        // Register the user as a member
        $expiry = new \DateTime();
        $membership = new Membership();
        $membership->setContact($user);
        $membership->setMembershipType($membershipType);
        $membership->setExpiresAt($expiry->modify("+1 year"));
        $membership->setStartsAt(new \DateTime());
        $membership->setStatus(Membership::SUBS_STATUS_ACTIVE);
        $manager->persist($membership);
        $manager->flush();

        $user->setActiveMembership($membership);
        $manager->persist($user);
        $manager->flush();

    }
}