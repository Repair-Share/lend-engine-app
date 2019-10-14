<?php

namespace Tests\AppBundle\Controller\Contact;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ContactControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testContactAction()
    {
        $crawler = $this->client->request('GET', '/admin/contact');

        $this->assertContains('Add a new contact', $crawler->html());

        $form = $crawler->filter('form[name="contact"]')->form(array(
            'contact[firstName]' => "Seamus",
            'contact[lastName]'  => "O'Neill",
            'contact[email]'     => 'seamus'.rand().'@email.com',
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Seamus', $crawler->html());
    }
}