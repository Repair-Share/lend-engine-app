<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class BasketAddServiceController extends Controller
{
    /**
     * @Route("basket/add-service/{itemId}", requirements={"itemId": "\d+"}, name="basket_add_service")
     */
    public function basketAddServiceItem($itemId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Services\Loan\LoanService $loanService */
        $loanService = $this->get("service.loan");

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get("service.contact");

        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        // FIND THE ITEM
        /** @var \AppBundle\Entity\InventoryItem $product */
        $product = $itemRepo->find($itemId);

        if ($product->getItemType() != InventoryItem::TYPE_SERVICE) {
            $this->addFlash('error', "This is not a service item.");
            return $this->redirectToRoute('home');
        }

        if (!$this->getUser()) {
            $this->addFlash('error', "You're not logged in. Please log in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$product->getPriceSell()) {
            $product->setPriceSell(0);
        }

        if (!$qty = (int)$request->get('qty')) {
            $qty = 1;
        }

        if ($request->get('add-to-loan')) {

            // Can only add to PENDING or RESERVED loans
            $loanId = $this->get('session')->get('active-loan');

            $loan = $loanService->get($loanId);

            if (!in_array($loan->getStatus(), [Loan::STATUS_PENDING, Loan::STATUS_RESERVED])) {
                $this->addFlash('error', "You can only add extra items to pending loans or reservations.");
                return $this->redirectToRoute('public_loan', ['loanId' => $loanId]);
            }

            $row = new LoanRow();
            $row->setDueInAt(new \DateTime()); // required as a default
            $row->setProductQuantity($qty);
            $row->setLoan($loan);
            $row->setInventoryItem($product);
            $row->setFee($product->getPriceSell());
            $loan->addLoanRow($row);

            $em->persist($loan);
            $em->flush();

            $this->get('session')->set('active-loan', null);

            return $this->redirectToRoute('public_loan', ['loanId' => $loanId]);

        } else {

            // Create them a basket if there isn't one yet
            if (!$basket = $basketService->getBasket()) {
                if ($request->get('contactId')) {
                    $basketContactId = $request->get('contactId');
                } else if ($this->get('session')->get('sessionUserId')) {
                    $basketContactId = $this->get('session')->get('sessionUserId');
                } else {
                    $basketContactId = $this->getUser()->getId();
                }

                if (!$basket = $basketService->createBasket($basketContactId)) {
                    $this->addFlash('error', "You don't have an active membership. Please check your account.");
                    return $this->redirectToRoute('home');
                }
            }

            // The basket only stores partial [serialized] contact info so get the full contact
            $contact = $contactService->get($basket->getContact()->getId());

            if (!$contact->getActiveMembership()) {
                $this->addFlash('error', "You don't have an active membership. Please check your account.");
                return $this->redirectToRoute('home');
            }

            if (!$basket) {
                $this->addFlash('error', "There was an error trying to create you a basket, sorry. Please check you have an active membership.");
                return $this->redirectToRoute('home');
            }

            $row = new LoanRow();
            $row->setProductQuantity($qty);
            $row->setLoan($basket);
            $row->setInventoryItem($product);
            $row->setFee($product->getPriceSell());
            $basket->addLoanRow($row);

            $msg = $this->get('translator')->trans('msg_success.basket_item_added', [], 'member_site');
            $this->addFlash('success', $product->getName().' '.$msg);

            $basketService->setBasket($basket);

            return $this->redirectToRoute('basket_show');
        }

    }
}
