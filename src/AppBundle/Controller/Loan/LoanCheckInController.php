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

            if (!$assignToContact = $form->get('contact')->getData()) {
                $assignToContact = null;
            }

            $loan = $loanRow->getLoan();

            // Perform the main action for each item
            $checkInItems = $request->get('check_in');

            foreach ($checkInItems AS $rowId) {
                $loanRow = $loanRowRepo->find($rowId);
                if ($service->checkInRow($toLocation, $loanRow, $userNote, $checkInFee, $assignToContact)) {
                    $this->addFlash('success', $loanRow->getInventoryItem()->getName().' checked in to "'.$toLocation->getName().'"');
                } else {
                    foreach ($service->errors AS $error) {
                        $this->addFlash("error", $error);
                    }
                }
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

        return $this->render('member_site/loan/loan_check_in.html.twig', array(
            'loanRow' => $loanRow,
            'form' => $form->createView(),
            'daysLate' => $daysLate,
            'user' => $loanRow->getLoan()->getContact()
        ));

    }


}