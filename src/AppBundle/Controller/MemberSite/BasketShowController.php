<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Serializer\Denormalizer\LoanDenormalizer;
use AppBundle\Serializer\Denormalizer\ContactDenormalizer;
use AppBundle\Serializer\Denormalizer\InventoryItemDenormalizer;
use AppBundle\Serializer\Denormalizer\LoanRowDenormalizer;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketShowController
 * @package AppBundle\Controller\MemberSite
 */
class BasketShowController extends Controller
{
    /**
     * @Route("basket", name="basket_show")
     */
    public function showBasket()
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        if (!$basket = $basketService->getBasket()) {
            $this->addFlash('error', "No basket found.");
            return $this->redirectToRoute('home');
        }

        $contactId = $basket->getContact()->getId();

        if (!$contact = $contactService->get($contactId)) {
            $this->addFlash('error', "Contact {$contactId} not found.");
            return $this->redirectToRoute('home');
        }

        $contactBalance = $contact->getBalance();

        return $this->render('member_site/pages/basket.html.twig', [
            'user' => $contact,
            'reservationFee' => $this->get('settings')->getSettingValue('reservation_fee'),
            'contactBalance' => $contactBalance
        ]);
    }

}
