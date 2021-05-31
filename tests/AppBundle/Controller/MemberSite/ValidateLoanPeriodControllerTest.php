<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ValidateLoanPeriodControllerTest extends AuthenticatedControllerTest
{
    private function getUri($itemId, $timeFrom = '', $timeTo = '', $loanId = '')
    {
        return '/validate-loan-period?itemId=' . urlencode($itemId) .
               '&timeFrom=' . urlencode($timeFrom) .
               '&timeTo=' . urlencode($timeTo) .
               '&loanId=' . urlencode($loanId);
    }

    public function testLoanPeriodDateParams()
    {
        $itemId = $this->helpers->createItem($this->client);

        $scenarios = [
            [
                'itemId' => null,
                'from'   => null,
                'to'     => null,
                'expect' => 'No item ID'
            ],
            [
                'itemId' => -1,
                'from'   => null,
                'to'     => null,
                'expect' => 'Item not found'
            ],
            [
                'itemId' => $itemId,
                'from'   => 'Invalid date',
                'to'     => null,
                'expect' => 'No time from'
            ],
            [
                'itemId' => $itemId,
                'from'   => null,
                'to'     => 'Invalid date',
                'expect' => 'No time to'
            ]
        ];

        foreach ($scenarios as $scenario) {

            $uri = $this->getUri(
                $scenario['itemId'],
                $scenario['from'],
                $scenario['to']
            );

            $this->client->request('GET', $uri);
            $response = $this->client->getResponse();

            $this->assertSame(200, $response->getStatusCode());

            $responseData = json_decode($response->getContent(), true);

            $this->assertIsArray($responseData);
            $this->assertArrayHasKey('error', $responseData);
            $this->assertContains($scenario['expect'], $responseData['error']);

        }

        $uri = $this->getUri(
            $itemId
        );

        $this->client->request('GET', $uri);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('0', $responseData);
        $this->assertContains('ok', $responseData[0]);

    }
}