<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Payment;
use AppBundle\Entity\Note;
use AppBundle\Form\Type\ItemCheckInType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class LoanCheckInController extends Controller
{

    /**
     * @Route("loan-row/{loanRowId}/check-in/", name="loan_check_in", defaults={"loanRowId" = 0}, requirements={"loanRowId": "\d+"})
     * @param Request $request
     * @param $loanRowId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function loanCheckIn(Request $request, $loanRowId)
    {

        $em = $this->getDoctrine()->getManager();

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        /** @var \AppBundle\Entity\LoanRow $loanRow */
        $loanRowRepo = $em->getRepository('AppBundle:LoanRow');

        if (!$loanRow = $loanRowRepo->find($loanRowId)) {
            $this->addFlash("error", "We couldn't find the loan row to check in, please try again");
            return $this->redirectToRoute('home');
        }

        if ($loanRow->getCheckedInAt()) {
            $this->addFlash('error', "This item has already been checked in.");
            return $this->redirectToRoute('public_loan', ['loanId' => $loanRow->getLoan()->getId()]);
        }

        /** @var \AppBundle\Entity\Site $site */
        if (!$site = $user->getActiveSite()) {
            if (!$site = $em->getRepository('AppBundle:Site')->find(1)) {
                $this->addFlash('error', "Site with ID 1 was not found - please contact support.");
                return $this->redirectToRoute('public_loan', ['loanId' => $loanRow->getLoan()->getId()]);
            }
        }

        $defaultCheckInLocation = $site->getDefaultCheckInLocation();

        // Calculate any late return fee
        $daysLate = 0;
        $today = new \DateTime();
        $interval = $today->diff( $loanRow->getDueInAt() );
        if ( $loanRow->getDueInAt() < $today ) {
            $daysLate = $interval->days;
        }

        $dailyOverdueFee = (float)$this->get('settings')->getSettingValue('daily_overdue_fee');

        // Apply customer discount
        if ($activeMembership = $loanRow->getLoan()->getContact()->getActiveMembership()) {
            if ($activeMembership->getMembershipType()->getDiscount() > 0) {
                $discount = $activeMembership->getMembershipType()->getDiscount();
                $dailyOverdueFee = $dailyOverdueFee - round($dailyOverdueFee * $discount/100,2);
            }
        }

        $lateFee = number_format($dailyOverdueFee * $daysLate, 2);

        $formOptions = array(
            'action' => $this->generateUrl('loan_check_in', ['loanRowId' => $loanRowId]),
            'em' => $em,
            'defaultCheckInLocation' => $defaultCheckInLocation,
            'lateFee' => $lateFee,
            'activeSite' => $user->getActiveSite(),
        );

        $form = $this->createForm(ItemCheckInType::class, null, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $userNote   = $form->get('notes')->getData();
            $checkInFee = $form->get('feeAmount')->getData();
            $toLocation = $form->get('location')->getData();

            if (!$toLocation) {
                $this->addFlash('error', "No check-in location found, please check your selections.");
                $this->redirectToRoute('loan_check_in', ['loanRowId' => $loanRowId]);
            }

            if (!$assignToContact = $form->get('contact')->getData()) {
                $assignToContact = null;
            }

            $loan = $loanRow->getLoan();

            // Perform the main action for each item
            $checkInItems = $request->get('check_in');
            foreach ($checkInItems AS $rowId) {
                $loanRow = $loanRowRepo->find($rowId);
                $this->checkInItem($toLocation, $loanRow, $userNote, $checkInFee, $assignToContact);
            }

            $returnedRows = 0;
            foreach ($loan->getLoanRows() AS $row) {
                /** @var $row \AppBundle\Entity\LoanRow */
                if ($row->getIsReturned() or $row->getInventoryItem()->getItemType() != InventoryItem::TYPE_LOAN) {
                    // kits and stock items contribute to this number so we can close a loan with all loanable items returned
                    $returnedRows++;
                }
            }

            if (count($loan->getLoanRows()) == $returnedRows) {
                $loan->setStatus(Loan::STATUS_CLOSED);

                // Update the loan return date to now
                $loan->setTimeIn(new \DateTime());

                $em->persist($loan);
                try {
                    $em->flush();
                } catch (\Exception $generalException) {
                    $this->addFlash('error', 'Loan failed to complete check in.');
                    $this->addFlash('debug', $generalException->getMessage());
                }
            }

            return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

        }

        return $this->render('member_site/pages/loan_check_in.html.twig', array(
            'loanRow' => $loanRow,
            'form' => $form->createView(),
            'daysLate' => $daysLate,
            'user' => $loanRow->getLoan()->getContact()
        ));

    }

    /**
     * @param InventoryLocation $location
     * @param LoanRow $loanRow
     * @param string $userNote
     * @param int $checkInFee
     * @param null $contact
     */
    private function checkInItem(InventoryLocation $location,
                                 LoanRow $loanRow,
                                 $userNote = '',
                                 $checkInFee = 0,
                                 $assignToContact = null) {

        // Set up
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\WaitingList\WaitingListService $waitingListService */
        $waitingListService = $this->get('service.waiting_list');

        $loan           = $loanRow->getLoan();
        $inventoryItem  = $loanRow->getInventoryItem();

        if ( $inventoryService->itemMove($inventoryItem, $location, $loanRow, $assignToContact, $userNote) ) {

            $this->addFlash('success', $inventoryItem->getName().' checked in to "'.$location->getName().'"');

            $noteText = 'Checked in <strong>'.$inventoryItem->getName().'</strong>';
            if ($userNote) {
                $noteText .= '<br>'.$userNote;
            }

            // Add a fee
            if ($checkInFee > 0) {
                $payment = new Payment();
                $payment->setAmount(-$checkInFee);
                $payment->setContact($loanRow->getLoan()->getContact());
                $payment->setLoan($loanRow->getLoan());
                $payment->setNote("Check-in fee for ".$inventoryItem->getName().".");
                $payment->setCreatedBy($user);
                $payment->setInventoryItem($inventoryItem);
                $em->persist($payment);

                try {
                    $em->flush();
                    $this->addFlash('success', 'Check-in fee added to member account.');
                    $noteText .= ' (check-in fee '.number_format($checkInFee, 2).")";
                    $contactService->recalculateBalance($loanRow->getLoan()->getContact());
                } catch (\Exception $generalException) {

                }
            }

            // Add a note to the loan and contact
            $note = new Note();
            $note->setCreatedBy($user);
            $note->setLoan($loan);
            $note->setContact($loanRow->getLoan()->getContact());
            $note->setText($noteText);
            $em->persist($note);
            try {
                $em->flush();
            } catch (\Exception $generalException) {
                $this->addFlash("error", "There was an error checking in item: " . $inventoryItem->getName());
            }

            // Process items that may be on the waiting list
            $waitingListService->process($inventoryItem);

        }
    }

}