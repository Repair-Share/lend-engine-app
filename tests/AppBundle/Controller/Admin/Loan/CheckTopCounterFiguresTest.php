<?php

namespace Tests\AppBundle\Controller\Admin\Loan;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class CheckTopCounterFiguresTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();

        foreach (['RESERVED', 'ACTIVE', 'OVERDUE'] as $status) {

            $contactId = $this->helpers->createContact(
                $this->client,
                'Test contact ' . uniqid()
            );

            $this->helpers->subscribeContact(
                $this->client,
                $contactId
            );

            $itemId = $this->helpers->createItem($this->client);

            switch ($status) {
                case 'RESERVED':
                    $this->helpers->createLoan($this->client, $contactId, [$itemId], 'reserve', 1);
                    break;
                case 'ACTIVE':
                    $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId], 'checkout', 1);
                    $this->helpers->checkoutLoan($this->client, $loanId);
                    break;
                case 'OVERDUE':
                    $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId], 'checkout', -10);
                    $this->helpers->checkoutLoan($this->client, $loanId);
                    break;
            }

        }

    }

    private function getStatusFigures($status)
    {
        $figures = [
            'topCounter'  => 0,
            'listCounter' => 0
        ];

        $to = date('Y-m-d');

        switch (strtolower($status)) {
            case 'reserved':
                $labelColor = 'orange';
                break;
            case 'pending':
                $labelColor = 'gray';
                break;
            case 'active':
                $labelColor = 'teal';
                break;
            case 'overdue':
                $labelColor = 'red';
                break;
            default:
                $labelColor = '';
                break;
        }

        if (!$labelColor) {
            return $figures;
        }

        $crawler = $this->client->request(
            'GET',
            '/admin/loan/list?filtered=1&date_type=date_out&date_from=2021-04-11&date_to=' . $to . '&status=' . $status . '&current_site=&from_site=&to_site='
        );

        $topCounter = (int)$crawler->filter('.pull-right .label.bg-' . $labelColor)->text();

        $this->client->request(
            'GET',
            '/admin/dt/loan/list?draw=1&status=' . $status . '&date_from=&date_to='
        );

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);

        $listCounter = (int)$responseData['recordsFiltered'];

        return [
            'topCounter'  => $topCounter,
            'listCounter' => $listCounter
        ];
    }

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

    public function testTopCounterFigures()
    {
        foreach (['RESERVED', 'PENDING', 'ACTIVE', 'OVERDUE'] as $status) {

            $figures = $this->getStatusFigures($status);

            $this->assertSame($figures['topCounter'], $figures['listCounter']);

        }
    }
}