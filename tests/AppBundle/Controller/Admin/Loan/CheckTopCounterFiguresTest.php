<?php

namespace Tests\AppBundle\Controller\Admin\Loan;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class CheckTopCounterFiguresTest extends AuthenticatedControllerTest
{
    public function testTopCounter()
    {
        $crawler = $this->client->request('GET', '/admin/loan/list');

        // Get the top counter figures
        $numberOfReserved = $crawler->filter('.label.bg-orange')->getNode(0)->nodeValue;
        $numberOfPending  = $crawler->filter('.label.bg-gray')->getNode(0)->nodeValue;
        $numberOfOnLoan   = $crawler->filter('.label.bg-teal')->getNode(0)->nodeValue;
        $numberOfOverdue  = $crawler->filter('.label.bg-red')->getNode(0)->nodeValue;

        foreach (['RESERVED', 'PENDING', 'ACTIVE', 'OVERDUE'] as $status) {

            $filter = '';

            switch ($status) {
                case 'RESERVED':
                    $filter = 'bg-orange';
                    break;
                case 'PENDING':
                    $filter = 'bg-gray';
                    break;
                case 'ACTIVE':
                    $filter = 'bg-teal';
                    break;
                case 'OVERDUE':
                    $filter = 'bg-red';
                    break;
            }

            // Get the top counter figure
            $topCounter = $crawler->filter('.label.' . $filter)->getNode(0)->nodeValue;

            // Check with the admin/dt list
            $url = '/admin/dt/loan/list?status=' . $status;

            $this->client->request('GET', $url);
            $this->assertContains('"recordsFiltered":' . $topCounter, $this->client->getResponse()->getContent());

        }

    }
}