<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\LoanRow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LoanSaveController
 * @package AppBundle\Controller
 */
class LoanSaveController extends Controller
{

    /**
     * @param $loanId
     * @param Request $request
     * @return Response
     * @throws \Exception
     * @Route("loan/{loanId}/save", requirements={"loanId": "\d+"}, name="loan_save")
     */
    public function saveChangesToLoan($loanId, Request $request)
    {

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in");
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        /** @var \AppBundle\Services\SettingsService $settings */
        $settings = $this->get('settings');

        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Repository\PaymentRepository $paymentRepo */
        $paymentRepo = $em->getRepository('AppBundle:Payment');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        $shippingItemId = $settings->getSettingValue('postal_shipping_item');

        if ($fees = $request->request->get('row_fee')) {

            // We're saving the loan
            $quantities = $request->request->get('row_qty');

            $loanContainsShipping = false;
            foreach ($loan->getLoanRows() AS $row) {
                /** @var $row \AppBundle\Entity\LoanRow */
                $row->setFee($fees[$row->getId()]);

                // Get all inventory for the item
                $inventory = $itemService->getInventory($row->getInventoryItem());

                // Check inventory levels
                foreach ($inventory AS $i) {
                    if ($i['locationId'] == $row->getItemLocation()->getId()) {
                        if ($i['qty'] < $quantities[$row->getId()]) {
                            $this->addFlash('error', 'Only '.$i['qty'].' of "'.$row->getInventoryItem()->getName().'" available at '.$i['siteName'].' / '.$i['locationName']);
                            return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);
                        }
                    }
                }

                $row->setProductQuantity($quantities[$row->getId()]);
                $em->persist($row);

                if ($row->getInventoryItem()->getId() == $shippingItemId) {
                    $loanContainsShipping = true;
                }
            }

            $collectFrom = $request->request->get('collect_from');
            $loan->setCollectFrom($collectFrom);

            $shippingHasChanged = false;
            if ($loan->getCollectFrom() == "post") {

                $fee = $basketService->calculateShippingFee($loan);

                if ($fee > 0 && $loanContainsShipping == false && $shippingItemId) {
                    $shippingHasChanged = true;
                    if ($shippingItem = $itemService->find($shippingItemId)) {
                        $shippingRow = new LoanRow();
                        $shippingRow->setInventoryItem($shippingItem);
                        $shippingRow->setProductQuantity(1);
                        $shippingRow->setFee($fee);
                        $shippingRow->setLoan($loan);
                        $shippingRow->setDueInAt(new \DateTime()); // due to schema requirements
                        $loan->addLoanRow($shippingRow);
                        $em->persist($shippingRow);
                        $this->addFlash('success', "Loan is due to be posted: shipping fee has been added.");
                    }
                }
            } else {
                $loan->setShippingFee(0);
            }

            // Save any changes to fee amounts
            if ($fees = $request->request->get('fee')) {
                foreach ($fees AS $feeId => $feeAmount) {
                    $fee = $paymentRepo->find($feeId);
                    $fee->setAmount($feeAmount);
                    $em->persist($fee);
                }
            }

            try {
                $em->flush();
                if ($shippingHasChanged == false) {
                    $this->addFlash('success', "Saved changes OK.");
                }
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}