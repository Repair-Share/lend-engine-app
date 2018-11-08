<?php

// tests/AppBundle/Controller/Website/LoginTest.php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{

    public function testLoginPage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertEquals(1, $crawler->filter('#username')->count());
        $this->assertEquals(1, $crawler->filter('#password')->count());
    }

    public function testRegisterPage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/');

        $this->assertEquals(1, $crawler->filter('#fos_user_registration_form_firstName')->count());
    }

}