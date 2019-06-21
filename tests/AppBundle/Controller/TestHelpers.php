<?php

/**
 * A class to create data for functional tests
 */

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;

class TestHelpers extends AuthenticatedControllerTest
{

    /**
     * @param Client $client
     * @return int
     */
    public function createContact(Client $client)
    {
        $crawler = $client->request('GET', '/admin/contact');
        $this->assertContains('Add a new contact', $crawler->html());

        $rand = rand(1,1000);
        $form = $crawler->filter('form[name="contact"]')->form(array(
            'contact[firstName]' => "Test ".$rand,
            'contact[lastName]'  => "Contact",
            'contact[email]'     => 'basket'.$rand.'@email.com',
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $contactId = (int)$crawler->filter('#contact-id')->text();
        $this->assertGreaterThan(0, $contactId);

        return $contactId;
    }

    /**
     * @param Client $client
     * @param $contactId
     */
    public function subscribeContact(Client $client, $contactId)
    {
        // Subscribe a contact to membership type 1
        $crawler = $client->request('GET', '/admin/membership/contact/'.$contactId);
        $this->assertContains('New membership for', $crawler->html());

        $form = $crawler->filter('form[name="membership"]')->form(array(
            'membership[membershipType]' => 1,
            'membership[price]'          => 15,
            'membership[paymentMethod]'  => 1,
            'membership[paymentAmount]'  => 15
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains('Membership saved', $crawler->html());

        // Confirm the user now has a membership
        $membershipId = (int)$crawler->filter('#active-membership-id')->text();
        $this->assertGreaterThan(0, $membershipId);
    }

    /**
     * @param Client $client
     * @param $contactId
     */
    public function addCredit(Client $client, $contactId)
    {
        $crawler = $client->request('GET', '/member/add-credit?c='.$contactId);
        $this->assertContains('Add credit', $crawler->html());

        $form = $crawler->filter('form[name="payment"]')->form(array(
            'paymentMethod' => 1,
            'paymentAmount' => 100,
            'paymentNote'   => 'Payment note',
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $client->followRedirect();
    }

    public function createEvent(Client $client)
    {
        $crawler = $client->request('GET', '/member/add-credit?c=');
        $this->assertContains('Add credit', $crawler->html());
    }

}