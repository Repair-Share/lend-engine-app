<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketCreateController
 * @package AppBundle\Controller\MemberSite
 */
class BasketCreateController extends Controller
{
    /**
     * Clear any current basket and create one for the requested member
     * @param $contactId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/create/{contactId}", requirements={"contactId": "\d+"}, name="basket_create")
     */
    public function createBasketAction($contactId)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in first.");
            return $this->redirectToRoute('home');
        }

        if (!$basket = $basketService->createBasket($contactId)) {
            foreach ($basketService->errors AS $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('home');
        }

        return $this->redirectToRoute('basket_show');
    }
}
