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

        /** @var \AppBundle\Services\Maintenance\MaintenanceService $maintenanceService */
        $maintenanceService = $this->get('service.maintenance');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->get('service.inventory');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        /** @var \AppBundle\Services\Loan\CheckInService $service */
        $service = $this->get('service.checkin');

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
                return $this->redirectToRoute('loan_check_in', ['loanRowId' => $loanRowId]);
            }

            $loan = $loanRow->getLoan();
            $maintenancePlan = $form->get('maintenancePlan')->getData();

            // Perform the main action for each item
            $checkInItems = $request->get('check_in');
            $maintenanceActions = [];

            foreach ($checkInItems AS $rowId) {
                $loanRow = $loanRowRepo->find($rowId);
                $inventoryItem = $loanRow->getInventoryItem();

                if ($service->checkInRow($toLocation, $loanRow, $userNote, $checkInFee)) {

                    $this->addFlash('success', $loanRow->getInventoryItem()->getName().' checked in to "'.$toLocation->getName().'"');

                    if ($maintenancePlan) {
                        $mData = [
                            'itemId' => $inventoryItem->getId(),
                            'planId' => $maintenancePlan->getId(),
                            'note' => $userNote,
                            'date' => new \DateTime()
                        ];
                        if ($m = $maintenanceService->scheduleMaintenance($mData)) {
                            $maintenanceActions[] = $m;
                        }
                    }

                } else {
                    foreach ($service->errors AS $error) {
                        $this->addFlash("error", $error);
                    }
                }
            }

            $inventoryItemQuantities = [];
            $inventoryItemFees = [];
            $returnedRows = 0;
            foreach ($loan->getLoanRows() AS $row) {
                /** @var $row \AppBundle\Entity\LoanRow */
                if ($row->getIsReturned() or $row->getInventoryItem()->getItemType() != InventoryItem::TYPE_LOAN) {
                    // kits, services and stock items contribute to this number so we can close a loan with all loanable items returned
                    $returnedRows++;
                }
                if ($row->getInventoryItem()->getItemType() == InventoryItem::TYPE_STOCK) {
                    $itemId = $row->getInventoryItem()->getId();
                    $inventoryItemQuantities[$itemId] = $row->getProductQuantity();
                    $inventoryItemFees[$itemId] = $row->getFee();
                }
            }

            if ($returnedItems = $request->get('return_qty')) {
                foreach ($returnedItems AS $itemId => $qty) {
                    $noteText = 'Returned on loan '.$loan->getId();
                    if ($qty > $inventoryItemQuantities[$itemId]) {
                        $this->addFlash('error', "You can't return more than you sold. Inventory was not added.");
                        continue;
                    }

                    $qty = floatval($qty);

                    if (!$qty) {
                        continue;
                    }

                    if ($inventoryService->addInventory($itemId, $qty, $toLocation->getId(), $noteText)) {
                        // Add a negative line to the loan
                        $item = $itemService->find($itemId);
                        $loanRow = new LoanRow();
                        $loanRow->setLoan($loan);
                        $loanRow->setInventoryItem($item);
                        $loanRow->setProductQuantity(-$qty);
                        $loanRow->setFee($inventoryItemFees[$itemId]);
                        $loanRow->setDueInAt(new \DateTime()); // due to schema constraints
                        $em->persist($loanRow); // flushed at the end

                        // Put the amount back on to account
                        $refund = new Payment();
                        $refund->setType(Payment::PAYMENT_TYPE_PAYMENT);
                        $refund->setAmount($inventoryItemFees[$itemId]);
                        $refund->setInventoryItem($item);
                        $refund->setLoan($loan);
                        $refund->setContact($loan->getContact());
                        $refund->setCreatedBy($user);
                        $refund->setNote("Returned {$qty} ".$item->getName());
                        $refund->setPaymentDate(new \DateTime());
                        $em->persist($refund);

                        $note = new Note();
                        $note->setLoan($loan);
                        $note->setCreatedBy($user);
                        $note->setText("Returned {$qty} ".$item->getName());
                        $em->persist($note);
                    } else {
                        foreach ($inventoryService->errors AS $error) {
                            $this->addFlash('error', $error);
                        }
                    }
                }
            }

            // Send an email to the provider of the plan
            if ($maintenancePlan && $maintenancePlan->getProvider() && count($maintenanceActions) > 0) {

                /** @var \AppBundle\Entity\Contact $provider */
                $provider      = $maintenancePlan->getProvider();
                $toEmail       = $provider->getEmail();
                $toName        = $provider->getName();

                $token = $contactService->generateAccessToken($provider);
                $loginUri = $tenantService->getTenant(false)->getDomain(true);
                $loginUri .= '/access?t='.$token.'&e='.urlencode($provider->getEmail());
                $loginUri .= '&r=/admin/maintenance/list&assignedTo='.$provider->getId();

                $message = $this->renderView(
                    'emails/maintenance_due.html.twig',
                    [
                        'assignee' => $provider,
                        'maintenance' => $maintenanceActions,
                        'domain' => $tenantService->getAccountDomain(),
                        'loginUri' => $loginUri
                    ]
                );

                // Send the email
                $subject = 'You have been assigned item(s) for maintenance';
                $emailService->send($toEmail, $toName, $subject.' | '.$loanRow->getLoan()->getId(), $message, true);

            }

            // All loanable items are returned
            if (count($loan->getLoanRows()) == $returnedRows) {
                $loan->setStatus(Loan::STATUS_CLOSED);
                // Update the loan return date to now
                $loan->setTimeIn(new \DateTime());
            }

            $em->persist($loan);
            try {
                $em->flush();
                $contactService->recalculateBalance($loan->getContact());
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'Loan failed to complete check in.');
                $this->addFlash('error', $generalException->getMessage());
            }

            return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

        }

        return $this->render('member_site/loan/loan_check_in.html.twig', array(
            'loanRow' => $loanRow,
            'form' => $form->createView(),
            'daysLate' => $daysLate,
            'user' => $loanRow->getLoan()->getContact()
        ));

    }


}