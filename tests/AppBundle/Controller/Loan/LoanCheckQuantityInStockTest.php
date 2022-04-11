<?php

namespace Tests\AppBundle\Controller\Loan;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanCheckQuantityInStockTest extends AuthenticatedControllerTest
{
    public function testLoanQuantity()
    {
        // Step 0: Create helper data
        /*$contactId = $this->helpers->createContact(
            $this->client,
            'Test contact ' . uniqid()
        );

        $this->helpers->subscribeContact(
            $this->client,
            $contactId
        );*/

        // Step 1: Create a new stock item
        $stockItemName = "Quantity stock test " . rand();
        $stockItemId   = $this->helpers->createItem($this->client, $stockItemName, [
            'type'      => 'stock',
            'priceSell' => 3.00
        ]);

        // Step 2: Add 1 into the stock
        $this->helpers->addInventory(
            $this->client,
            1,
            $stockItemId,
            1
        );

        // Step 3: Add a basket with quantity 2
        $loanId = $this->helpers->createBasket(
            $this->client,
            $stockItemId,
            2
        );

        // Step 4: Tries to check out the loan with quantity 2 -> Should be failed
        $html = $this->helpers->checkoutLoan(
            $this->client,
            $loanId,
            true
        );

        $this->assertContains('Not enough stock of', $html);

        // Step 5: Update the loan quantity to 1
        $this->helpers->updateLoanRowQuantity(
            $this->client,
            $loanId,
            $stockItemId,
            1
        );

        // Step 6: Tries to check out the loan with quantity 1 -> Should be o
        $html = $this->helpers->checkoutLoan(
            $this->client,
            $loanId,
            true
        );

        $this->assertContains('Redirecting to /loan/' . $loanId, $html);

    }
}