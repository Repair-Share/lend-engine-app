<?php

namespace AppBundle\Controller\Payment;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RemovePaymentMethodController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("remove-payment-method", name="remove_payment_method")
     */
    public function removePaymentMethod(Request $request)
    {
        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        $paymentMethodId = $request->get('paymentMethodId');
        $c = $request->get('c');

        if (!$stripeService->removePaymentMethod($paymentMethodId)) {
            foreach ($stripeService->errors AS $error) {
                $this->addFlash("error", $error);
            }
        } else {
            $this->addFlash("success", "Removed card OK");
        }

        return $this->redirectToRoute('add_credit', ['c' => $c]);
    }
}
