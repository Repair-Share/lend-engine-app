<?php

namespace Tests\AppBundle\Services\Schedule;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class EmailReservationRemindersTest extends AuthenticatedControllerTest
{
    private function setUpSettings($tz)
    {
        $this->helpers->setSettingValue('org_timezone', $tz);
        $this->helpers->setSettingValue('automate_email_reservation_reminder', 1);
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
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId], 'reserve', 1);

        return $loanId;
    }

    public function testTimezoneHours()
    {
        foreach (['Europe/Budapest', 'Europe/London'] as $tz) {

            $this->setUpSettings($tz);
            $this->setUpLoan();

            $container = $this->getContainer();

            $scheduleHandler = $container->get('service.schedule_reservation_reminders');

            $d = new \DateTime();
            $d->setTime(9, 0, 0);
            $d->setDate(date('Y'), date('m'), date('d') + 1);

            $this->assertContains($d->format('d F Y g:i a'), $scheduleHandler->processReservationReminders());

        }
    }
}