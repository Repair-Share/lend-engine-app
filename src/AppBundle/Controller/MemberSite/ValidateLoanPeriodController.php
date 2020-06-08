<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Services\Booking\BookingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller
 */
class ValidateLoanPeriodController extends Controller
{
    /**
     * Called from the item booking calendar to verify that the loan can be placed
     * @return Response
     * @Route("validate-loan-period", name="validate_loan_period")
     */
    public function validateLoanPeriod(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if (!$timeFrom  = new \DateTime($request->get('timeFrom'))) {
            return new JsonResponse(['error' => 'No time from']);
        }

        if (!$timeTo    = new \DateTime($request->get('timeTo'))) {
            return new JsonResponse(['error' => 'No time to']);
        }

        if (!$itemId    = $request->get('itemId')) {
            return new JsonResponse(['error' => 'No item ID']);
        }

        $loanId    = $request->get('loanId');

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var $item \AppBundle\Entity\InventoryItem */
        if (!$item = $itemRepo->find($itemId)) {
            return new JsonResponse(['error' => 'Item not found']);
        }

        // Check if the item is already reserved for the chosen dates
        // Includes any buffer period added
        /** @var \AppBundle\Services\Loan\CheckoutService $checkoutService */
        $checkoutService = $this->get("service.checkout");
        if ($checkoutService->isItemReserved($item, $timeFrom, $timeTo, $loanId)) {
            $errors = [];
            foreach ($checkoutService->errors AS $error) {
                $errors[] = $error;
            }
            return new JsonResponse(['error' => 'Item is reserved or on loan for selected dates', 'detail' => $errors]);
        } else {

        }

        return new JsonResponse(['ok']);

    }
}