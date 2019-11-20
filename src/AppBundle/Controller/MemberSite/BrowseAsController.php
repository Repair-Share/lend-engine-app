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
 * Class BrowseAsController
 * @package AppBundle\Controller\MemberSite
 */
class BrowseAsController extends Controller
{
    /**
     * @return Response
     * @Route("switch-to/{contactId}", requirements={"contactId": "\d+"}, name="switch_contact")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function switchContactAction(Request $request, $contactId)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        if (!$contact = $contactService->get($contactId)) {
            $this->addFlash('error', "Couldn't find a contact with ID {$contactId}.");
            return $this->redirectToRoute('home');
        }

        // If we have a basket, also switch the user
        if ($basket = $basketService->getBasket()) {

            if (!$contact->getActiveMembership()) {
                $this->addFlash('error', "This member doesn't have an active membership.");
                return $this->redirectToRoute('basket_show');
            }

            $basket->setContact($contact);
            $this->addFlash('success', "Changed user to <strong>".$contact->getName().'</strong>');

            $basketService->setBasket($basket);
        }

        $basketService->setSessionUser($contactId);

        if ($request->get('go') == 'basket') {
            return $this->redirectToRoute('basket_show');
        } else if ($request->get('go') == 'events') {
            return $this->redirectToRoute('event_list');
        } else if ($itemId = $request->get('itemId')) {
            return $this->redirectToRoute('public_product', ['productId' => $itemId]);
        } else if ($request->get('new') == 'loan' || $request->get('new') == 'reservation')  {
            // Redundant now I think
            return $this->redirectToRoute('basket_create', ['contactId' => $contactId]);
        } else {
            return $this->redirectToRoute('home');
        }

    }

}
