<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SellLoanItemController
 * @package AppBundle\Controller\MemberSite
 */
class SellLoanItemController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("loan/row/{rowId}/sell", requirements={"itemId": "\d+"}, name="sell_loan_item")
     */
    public function convertLoanRowToStockItem($rowId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRowRepository $loanRowRepo */
        $loanRowRepo = $em->getRepository('AppBundle:LoanRow');

        /** @var \AppBundle\Entity\LoanRow $row */
        $row = $loanRowRepo->find($rowId);
        $loan = $row->getLoan();

        /** @var \AppBundle\Repository\InventoryLocationRepository $locationRepo */
        $locationRepo = $em->getRepository('AppBundle:InventoryLocation');

        $onLoanLocation = $locationRepo->find(1);
        $row->setItemLocation($onLoanLocation);

        $item = $row->getInventoryItem();
        $item->setItemType(InventoryItem::TYPE_STOCK);
        $item->setShowOnWebsite(false);
        $item->setInventoryLocation(null);

        // Add a note
        $note = new Note();
        $note->setCreatedBy($this->getUser());
        $note->setInventoryItem($item);
        $note->setText("Sold ".$item->getName()." to ".$loan->getContact()->getName());
        $note->setLoan($loan);
        $em->persist($note);

        // Change the name to break it out from an item group which may contain loanable items
        $item->setName($item->getName().' (sold)');

        $em->persist($row);
        $em->persist($item);

        // If all loan items are checked in, close the loan
        $loanContainsOpenRows = false;
        foreach ($loan->getLoanRows() AS $loanRow) {
            if ($loanRow->getCheckedInAt() == null && $loanRow->getInventoryItem()->getItemType() == InventoryItem::TYPE_LOAN) {
                $loanContainsOpenRows = true;
            }
        }
        if ($loanContainsOpenRows == false) {
            $loan->setStatus(Loan::STATUS_CLOSED);
            $em->persist($loan);
        }

        $em->flush();

        $this->addFlash('success', "The loan row has been converted to a stock item.<br>Add a fee to the loan for the sale price, and then add credit to pay for the balance.");

        return $this->redirectToRoute('public_loan', ['loanId' => $row->getLoan()->getId()]);
    }
}
