<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Helpers\DateTimeHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class BasketAddItemController extends Controller
{
    /**
     * @Route("basket/add/{itemId}", requirements={"itemId": "\d+"}, name="basket_add_item")
     */
    public function basketAddItem($itemId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\LoanRepository $loanRepo */
        $loanRepo = $em->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');

        /** @var \AppBundle\Services\Loan\CheckoutService $checkoutService */
        $checkoutService = $this->get("service.checkout");

        /** @var \AppBundle\Services\Loan\LoanService $loanService */
        $loanService = $this->get("service.loan");

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get("service.contact");

        /** @var \AppBundle\Services\BasketService $basketService */
        $basketService = $this->get('service.basket');

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        // FIND THE ITEM
        /** @var \AppBundle\Entity\InventoryItem $product */
        $product = $itemRepo->find($itemId);

        // Validate sites
        if (!$request->get('from_site') || !$request->get('to_site')) {
            $this->addFlash('error', "There was an error trying to find the site you chose. Please log out/in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$request->get('date_from') || !$request->get('date_to')) {
            $this->addFlash('error', "Sorry, we couldn't determine loan dates. Please log out/in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$this->getUser()) {
            $this->addFlash('error', "You're not logged in. Please log in and try again.");
            return $this->redirectToRoute('home');
        }

        // If we're adding to an existing loan
        if ($loanId = $this->get('session')->get('active-loan')) {
            $basket = $loanRepo->find($loanId);
        } else if ($loanId = $request->get('active-loan')) {
            $basket = $loanRepo->find($loanId);
        } else if (!$basket = $basketService->getBasket()) {
            // Create them a basket if there isn't one yet
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

        if (in_array(strtolower($basket->getStatus()), ['active', 'closed', 'cancelled', 'reserved', 'overdue'])) {
            $errorStr = "You can't add an item to a loan when it's " . strtolower($basket->getStatus()) . ".";
            $this->addFlash('error', $errorStr);

            $this->get('session')->set('active-loan', null);
            $this->get('session')->set('active-loan-type', null);

            return $this->redirectToRoute('public_loan', ['loanId' => $loanId]);
        }

        // Verify user can borrow more items, if there's a limit on their membership type
        $maxItems = $contact->getActiveMembership()->getMembershipType()->getMaxItems();
        if ($maxItems > 0) {

            // Count loan items in basket
            $countLoanItemsInBasket = 0;
            foreach ($basket->getLoanRows() AS $row) {
                if ($row->getInventoryItem()->getItemType() == "loan") {
                    $countLoanItemsInBasket++;
                }
            }

            $filter = [
                'status' => Loan::STATUS_ACTIVE,
                'contact' => $basket->getContact(),
                'isOnLoan' => true // make sure we only include loanable items which are still on loan (ie no kits)
            ];
            $itemsOnLoan = $loanService->countLoanRows($filter);

            $filter = [
                'status' => Loan::STATUS_OVERDUE,
                'contact' => $basket->getContact(),
                'isOnLoan' => true // make sure we only include loanable items which are still on loan (ie no kits)
            ];
            $itemOverdue = $loanService->countLoanRows($filter);

            $totalQty = $itemsOnLoan + $itemOverdue + $countLoanItemsInBasket;
            if ($totalQty >= $maxItems) {
                $this->addFlash('error', "You've already got {$totalQty} items on loan and in basket. The maximum for your membership is {$maxItems}.");
                return $this->redirectToRoute('home');
            }

        }

        if (!$basket) {
            $this->addFlash('error', "There was an error trying to create you a basket, sorry. Please check you have an active membership.");
            return $this->redirectToRoute('home');
        }

        // Catch-all if no qty is given
        if (!$qtyRequired = $request->get('qty')) {
            $qtyRequired = 1;
        }

        // Prevent user from adding the same item again
        foreach ($basket->getLoanRows() AS $row) {
            if ($row->getInventoryItem()->getId() == $itemId) {
                $msg = $this->get('translator')->trans('msg_success.basket_item_exists', [], 'member_site');
                $this->addFlash('success', $product->getName().' '.$msg);
                if ($qtyRequired > 1) {
                    $this->addFlash("error", "Please remove this item from basket before adding multiple quantities.");
                }
                if ($basket->getId()) {
                    return $this->redirectToRoute('public_loan', ['loanId' => $basket->getId()]);
                } else {
                    return $this->redirectToRoute('basket_show');
                }

            }
        }

        $fee = $request->get('item_fee');

        // Reservation fee
        $reservationFee = $request->get('booking_fee');
        $basket->setReservationFee($reservationFee);

        if (!$siteFrom = $siteRepo->find($request->get('from_site'))) {
            throw new \Exception("Cannot find site ".$request->get('from_site'));
        }

        if (!$siteTo   = $siteRepo->find($request->get('to_site'))) {
            throw new \Exception("Cannot find site ".$request->get('to_site'));
        }

        $dFrom = new \DateTime($request->get('date_from').' '.$request->get('time_from'));
        $dTo   = new \DateTime($request->get('date_to').' '.$request->get('time_to'));

        if ($basket->getId()) { // Adding to the existing loan doesn't run the set basket service with time corrections
            $dFrom = DateTimeHelper::changeLocalTimeToUtc($settingsService->getSettingValue('org_timezone'), $dFrom);
            $dTo   = DateTimeHelper::changeLocalTimeToUtc($settingsService->getSettingValue('org_timezone'), $dTo);
        }

        if ($checkoutService->isItemReserved($product, $dFrom, $dTo, null)) {
            $this->addFlash('error', "This item is reserved or on loan for your selected dates");
            foreach ($checkoutService->errors AS $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('public_product', ['productId' => $product->getId()]);
        }

        $row = new LoanRow();
        $row->setLoan($basket);
        $row->setInventoryItem($product);
        $row->setSiteFrom($siteFrom);
        $row->setSiteTo($siteTo);
        $row->setDueOutAt($dFrom);
        $row->setDueInAt($dTo);
        $row->setFee($fee);
        $row->setProductQuantity(1);
        $basket->addLoanRow($row);

        $basket->setCollectFromSite($siteFrom);

        if ($product->getItemType() == InventoryItem::TYPE_KIT) {

            /** @var \AppBundle\Entity\KitComponent $kitComponent */
            foreach ($product->getComponents() AS $kitComponent) {
                // We don't mind WHICH component by name is added if there are a few
                $componentName = $kitComponent->getComponent()->getName();
                if ($component = $this->getFirstAvailableItemByName($componentName, $dFrom, $dTo)) {
                    if ($component->getId() != $kitComponent->getComponent()->getId()) {
                        $this->addFlash('info', "We've substituted another {$componentName} as the one in the kit is not available. Please check the code and location.");
                    }
                    $row = new LoanRow();
                    $row->setLoan($basket);
                    $row->setInventoryItem($component);
                    $row->setSiteFrom($siteFrom);
                    $row->setSiteTo($siteTo);
                    $row->setDueOutAt($dFrom);
                    $row->setDueInAt($dTo);
                    $row->setFee(0);
                    $row->setProductQuantity(1);
                    $basket->addLoanRow($row);
                }
            }
        }

        $qtyFulfilled = 1;

        // If we're bulk adding, run through a loop for more rows
        if ($qtyRequired > 1) {
            // get other items with the same name, and find others which are available
            /** @var \AppBundle\Entity\InventoryItem $item */
            foreach ($itemRepo->findBy(['name' => $product->getName()]) AS $item) {
                if ($item->getId() == $product->getId()) {
                    continue;
                }
                if ($qtyFulfilled == $qtyRequired) {
                    continue;
                }
                if (!$checkoutService->isItemReserved($item, $dFrom, $dTo, null)) {
                    $row = new LoanRow();
                    $row->setLoan($basket);
                    $row->setInventoryItem($item);
                    $row->setSiteFrom($siteFrom);
                    $row->setSiteTo($siteTo);
                    $row->setDueOutAt($dFrom);
                    $row->setDueInAt($dTo);
                    $row->setFee($fee);
                    $row->setProductQuantity(1);
                    $basket->addLoanRow($row);
                    $qtyFulfilled++;
                }
            }
        }

        if ($qtyFulfilled < $qtyRequired) {
            $deficit = $qtyRequired - $qtyFulfilled;
            $this->addFlash("error", "{$deficit} not added :");
            foreach ($checkoutService->errors AS $e) {
                $this->addFlash("error", $e);
            }
        }

        // Shipping fee
        if ($basket->getCollectFrom() == "post") {
            $fee = $basketService->calculateShippingFee($basket);
            $basket->setShippingFee($fee);
        } else {
            $basket->setShippingFee(0);
        }

        if ($basket->getId()) {
            // We added to an existing loan
            $em->persist($basket);
            $em->flush();
            $this->addFlash('success', $qtyFulfilled. ' x ' .$product->getName().' added.');
            $this->get('session')->set('active-loan', null);
            return $this->redirectToRoute('public_loan', ['loanId' => $basket->getId()]);
        } else {
            $msg = $this->get('translator')->trans('msg_success.basket_item_added', [], 'member_site');
            $this->addFlash('success', $qtyFulfilled. ' x ' .$product->getName().' '.$msg);
            $basketService->setBasket($basket);
            return $this->redirectToRoute('basket_show');
        }

    }

    /**
     * @param $name
     * @param $dFrom
     * @param $dTo
     * @return InventoryItem|null
     */
    private function getFirstAvailableItemByName($name, $dFrom, $dTo)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Services\Loan\CheckoutService $checkoutService */
        $checkoutService = $this->get("service.checkout");

        /** @var \AppBundle\Entity\InventoryItem $item */
        foreach ($itemRepo->findBy(['name' => $name]) AS $item) {
            if (!$checkoutService->isItemReserved($item, $dFrom, $dTo, null)) {
                return $item;
            }
        }

        $this->addFlash("error", "Cannot add ".$name);
        foreach ($checkoutService->errors AS $error) {
            $this->addFlash('error', $error);
        }

        return null;
    }
}
