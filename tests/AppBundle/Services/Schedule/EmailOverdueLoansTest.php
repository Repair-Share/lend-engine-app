<?php

namespace Tests\AppBundle\Services\Schedule;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class EmailOverdueLoansTest extends AuthenticatedControllerTest
{
    private function setUpSettings($tz)
    {
        $container = $this->getContainer();

        $em   = $container->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:Setting');

        $setting = $repo->findOneBy(['setupKey' => 'org_timezone']);
        $setting->setSetupValue($tz);

        $setting = $repo->findOneBy(['setupKey' => 'automate_email_overdue_days']);
        $setting->setSetupValue(1);

        $em->persist($setting);
        $em->flush();
    }

    private function setUpLoan()
    {
        // Create items required
        $itemId = $this->helpers->createItem($this->client, "Item 1 / " . rand(1, 10000));

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId], 'checkout', -2);

        // Check out the loan
        $this->helpers->checkoutLoan($this->client, $loanId);

        return $loanId;
    }

    public function testTimezoneHours()
    {
        foreach (['Europe/Budapest', 'Europe/London'] as $tz) {

            $this->setUpSettings($tz);
            $this->setUpLoan();

            $container = $this->getContainer();

            $scheduleHandler = $container->get('service.schedule_overdue_loans');

            $d = new \DateTime();
            $d->setTime(17, 0, 0);
            $d->setDate(date('Y'), date('m'), date('d') - 1);

            $this->assertEquals($d->format('Y-m-d H:i'), $scheduleHandler->processOverdueEmails());

        }
    }
}