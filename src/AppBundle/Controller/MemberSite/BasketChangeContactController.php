<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketChangeContactController
 * @package AppBundle\Controller\MemberSite
 */
class BasketChangeContactController extends Controller
{
    /**
     * @Route("basket/set-contact/{contactId}", requirements={"contactId": "\d+"}, name="basket_set_contact")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function basketChangeContact($contactId)
    {
        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        if (!$basket = $basketService->getBasket()) {
            $basket = $basketService->createBasket($contactId);
        }

        if ($contact = $contactRepo->find($contactId)) {

            if (!$contact->getActiveMembership()) {
                $this->addFlash('error', "This member doesn't have an active membership.");
                return $this->redirectToRoute('basket_show');
            }

            $basket->setContact($contact);
            $this->addFlash('success', "This basket is now for <strong>".$contact->getName().'</strong>');
        }

        $basketService->setBasket($basket);
        $basketService->setSessionUser($contactId);

        return $this->redirectToRoute('basket_show');
    }
}
