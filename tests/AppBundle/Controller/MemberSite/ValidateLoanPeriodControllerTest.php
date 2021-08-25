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

    public function testExtendingLoan()
    {
        $itemId = $this->helpers->createItem($this->client, 'Item 1 / ' . rand(1, 10000));

        // Loan #1 from today (t) to t+1
        $contactId1 = $this->helpers->createContact($this->client);
        $this->helpers->subscribeContact($this->client, $contactId1);
        $this->helpers->addCredit($this->client, $contactId1);
        $loanId1 = $this->helpers->createLoan($this->client, $contactId1, [$itemId], 'checkout', 0);
        $this->helpers->checkoutLoan($this->client, $loanId1);

        // Loan #2 from t+2 to t+3
        $contactId2 = $this->helpers->createContact($this->client);
        $this->helpers->subscribeContact($this->client, $contactId2);
        $this->helpers->addCredit($this->client, $contactId2);
        $loanId2 = $this->helpers->createLoan($this->client, $contactId2, [$itemId], 'reserve', 2);
        $this->helpers->checkoutLoan($this->client, $loanId2);

        // Try to extend the first loan to t+5 -> Expect an error
        $time = new \DateTime();
        $time = $time->modify('5 days');

        $uri = '/validate-loan-period?itemId=' . $itemId
               . '&timeFrom=' . $time->format('Y-m-d 09:00:00')
               . '&timeTo=' . $time->format('Y-m-d 17:00:00')
               . '&loanId=' . $loanId1;

        $this->client->request('GET', $uri);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        // At this stage we only search in the response content and don't decode the JSON data to array
        $responseContent = $response->getContent();

        $this->assertNotContains('["ok"]', $responseContent);

        // Try to extend the first loan to t+1 -> Should be ok
        $time = new \DateTime();
        $time = $time->modify('1 days');

        $uri = '/validate-loan-period?itemId=' . $itemId
               . '&timeFrom=' . $time->format('Y-m-d 09:00:00')
               . '&timeTo=' . $time->format('Y-m-d 17:00:00')
               . '&loanId=' . $loanId1;

        $this->client->request('GET', $uri);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        // At this stage we only search in the response content and don't decode the JSON data to array
        $responseContent = $response->getContent();

        $this->assertContains('["ok"]', $responseContent);
    }
}