<?php

namespace Tests\AppBundle\Controller\Admin\Loan;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class CheckTopCounterFiguresTest extends AuthenticatedControllerTest
{
    public function testTopCounter()
    {
        $crawler = $this->client->request('GET', '/admin/loan/list');

        // Get the top counter figures
        $numberOf = [
            'RESERVED' => $crawler->filter('.label.bg-orange')->getNode(0)->nodeValue,
            'PENDING'  => $crawler->filter('.label.bg-gray')->getNode(0)->nodeValue,
            'ACTIVE'   => $crawler->filter('.label.bg-teal')->getNode(0)->nodeValue,
            'OVERDUE'  => $crawler->filter('.label.bg-red')->getNode(0)->nodeValue
        ];

        foreach (['RESERVED', 'PENDING', 'ACTIVE', 'OVERDUE'] as $status) {

            // Check with the admin/dt list
            $url = '/admin/dt/loan/list?status=' . $status;

            $this->client->request('GET', $url);
            $this->assertContains(
                '"recordsFiltered":' . $numberOf[$status],
                $this->client->getResponse()->getContent()
            );

        }

    }
}