<?php

namespace Tests\AppBundle\Controller\Loan;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanServiceTest extends AuthenticatedControllerTest
{
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

    public function testLoanWithStockItem()
    {
        foreach (['RESERVED', 'PENDING', 'ACTIVE', 'OVERDUE'] as $status) {

            $figures = $this->getStatusFigures($status);

            $this->assertSame($figures['topCounter'], $figures['listCounter']);

        }

    }
}