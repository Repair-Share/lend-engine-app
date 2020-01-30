<?php

namespace AppBundle\Controller\Loan;

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

        /** @var \AppBundle\Repository\LoanRepository $repo */
        $repo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Entity\Loan $loan */
        if (!$loan = $repo->find($loanId)) {
            $this->addFlash('error', "We couldn't find a loan with ID {$loanId}. ");
            return $this->redirectToRoute('home');
        }

        if ($fees = $request->request->get('row_fee')) {

            // We're saving the loan
            $quantities = $request->request->get('row_qty');

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
            }

            try {
                $em->flush();
                $this->addFlash('success', "Saved OK.");
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

}