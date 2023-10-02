<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class BrowseAsControllerTest extends AuthenticatedControllerTest
{
    public function testBrowseAs()
    {
        $contactName = 'Sample Test';

        // Create a new contact
        //$contactID = $this->helpers->createContact($this->client, $contactName);

        // TODO
        $contactID = 6;

        // Search for the contact
        $crawler = $this->client->request(
            'GET',
            '/member-search?go=&member-search=' . $contactName
        );

        $this->assertContains('Member search', $crawler->html());
        $this->assertContains('Browse / borrow as ' . $contactName, $crawler->html());

        // Browse as
        $crawler = $this->client->request(
            'GET',
            '/switch-to/' . $contactID . '?go='
        );

        $crawler = $this->client->followRedirect();

        $div = $crawler->filter('.admin-tools-highlight');

        $this->assertContains('Browsing as', $div->html());
        $this->assertContains($contactName, $div->html());

        $div = $crawler->filter('.admin-tools-highlight');
        $this->assertContains('Cancel', $div->parents()->html());
        $this->assertContains('<a href="/switch-to/1">Cancel</a>', $div->parents()->html());

        // Cancel
        $crawler = $this->client->request(
            'GET',
            '/switch-to/1?go='
        );

        $crawler = $this->client->followRedirect();

        $this->assertNotContains('Browsing as', $crawler->html());
    }
}