<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketConfirmController
 * @package AppBundle\Controller\MemberSite
 */
class BasketConfirmController extends Controller
{
    /**
     * Create the loan or reservation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/confirm", name="basket_confirm")
     */
    public function basketConfirmAction(Request $request)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        $formAction = $request->request->get('action');
        $rowFees = $request->request->get('row_fee');

        if (!$basket = $basketService->confirmBasket($formAction, $rowFees)) {
            foreach ($basketService->errors AS $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('home');
        } else {
            foreach ($basketService->messages AS $message) {
                $this->addFlash('success', $message);
            }
        }

        return $this->redirectToRoute('public_loan', ['loanId' => $basket->getId()]);
    }
}
