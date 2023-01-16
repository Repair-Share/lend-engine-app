<?php

namespace AppBundle\Controller\MemberSite\Item;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Maintenance;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\LoanExtendType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles all the pages on the static marketing site
 * Class SiteController
 * @package AppBundle\Controller
 */
class ItemController extends Controller
{

    /**
     * @Route("product/{productId}", requirements={"productId": "\d+"}, name="public_product")
     */
    public function showItemAction($productId, Request $request)
    {
        $security = $this->get('security.authorization_checker');

        if ($this->get('settings')->getSettingValue('site_is_private')
            && !$security->isGranted('ROLE_USER')) {
            $msg = $this->get('translator')->trans("public_misc.log_in_first", [], 'member_site');
            $this->addFlash("error", $msg);
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $repo */
        $repo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');

        /** @var $waitingListRepo \AppBundle\Repository\WaitingListItemRepository */
        $waitingListRepo = $this->getDoctrine()->getRepository('AppBundle:WaitingListItem');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var $product \AppBundle\Entity\InventoryItem */
        if (!$product = $repo->find($productId)) {
            $this->addFlash("error", "Item with ID {$productId} not found.");
            return $this->redirectToRoute('home');
        }

        // Check permissions to access
        if (!$security->isGranted('ROLE_ADMIN') && $product->getShowOnWebsite() == false) {
            $this->addFlash("error", "This item listing is not available for members.");
            return $this->redirectToRoute('home');
        }

        // If browsing as someone else, use their prices
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $this->getUser()->getId() != $sessionUserId) {
            $contact = $contactRepo->find($sessionUserId);
        } else {
            $contact = $this->getUser();
        }

        $defaultLoanDays = (int)$settingsService->getSettingValue('default_loan_days');
        $minLoanDays = (int)$settingsService->getSettingValue('min_loan_days');
        $maxLoanDays = (int)$settingsService->getSettingValue('max_loan_days');

        $loanEndDate = new \DateTime();
        $loanEndDate->modify("+ {$defaultLoanDays} days");

        // Calculate the item fee for the full loan period including any member discount
        $itemFee = $itemService->determineItemFee($product, $contact);

        // Usually 1, for a daily charge
        if (!$itemLoanDays = $product->getMaxLoanDays()) {
            $itemLoanDays = $defaultLoanDays;
        }
        $loanPeriod = $itemLoanDays;

        // Pro-rate to get a DAILY fee which is used on the calendar for bookings
        $dailyFee = 0;
        if ($itemLoanDays) {
            $dailyFee = round($itemFee / $itemLoanDays, 6);
        }

        $itemFee = round($itemFee, 2);

        $product->setLoanFee($itemFee);
        $product->setMaxLoanDays($itemLoanDays);

        if ($product->getImageName()) {
            $account_code   = $this->get('service.tenant')->getAccountCode();
            $s3Bucket       = $this->get('service.tenant')->getS3Bucket();
            $product->setImagePath($s3Bucket.$account_code.'/large/'.$product->getImageName());
        }

        $isMultiSite = $settingsService->getSettingValue('multi_site');

        $contactBalance = 0;
        if ($contact) {
            $contactBalance = $contact->getBalance();
        }

        $reservationFee = $settingsService->getSettingValue('reservation_fee');

        $sites = $product->getSites();
        if (count($sites) == 0) {
            /** @var $siteRepo \AppBundle\Repository\SiteRepository */
            $siteRepo = $this->getDoctrine()->getRepository('AppBundle:Site');
            $sites = $siteRepo->findAll();
        }

        $filter = [
            'contact'       => $contact,
            'removedAt'     => null,
            'inventoryItem' => $product
        ];
        $isOnWaitingList = false;
        if ($waitingListRepo->findOneBy($filter)) {
            $isOnWaitingList = true;
        }

        // Count similar items
        /** @var $itemRepo \AppBundle\Repository\InventoryItemRepository */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');
        $items = $itemRepo->findBy(['name' => $product->getName(), 'isActive' => true]);

        /** @var \AppBundle\Entity\Site $site */
        if (!$this->getUser() || !$site = $this->getUser()->getActiveSite()) {
            // Just use the first site
            $site = $sites[0];
        }

        $pickupTime = new \DateTime();
        $template = 'member_site/item/item.html.twig';
        $formView = null;

        $loanId = '';
        $loanStartAt = '';
        $itemDueInAt = '';

        if ($loanRowId = $request->get('extend')) {

            /** @var \AppBundle\Entity\LoanRow $loanRow */
            $loanRow = $em->getRepository('AppBundle:LoanRow')->find($loanRowId);
            $contact = $loanRow->getLoan()->getContact();
            $loanId  = $loanRow->getLoan()->getId();

            $timeZone = $settingsService->getSettingValue('org_timezone');
            $tz = new \DateTimeZone($timeZone);

            $loanStartAt = $loanRow->getDueOutAt()->setTimezone($tz)->format("Y-m-d H:i:00");
            $itemDueInAt = $loanRow->getDueInAt()->setTimezone($tz)->format("Y-m-d H:i:00");

            $stripeUseSavedCards = $settingsService->getSettingValue('stripe_use_saved_cards');
            if ($stripeUseSavedCards) {
                $contact = $contactService->loadCustomerCards($contact);
            }

            // Create the form
            $form = $this->createForm(LoanExtendType::class, null, [
                'em' => $em,
                'action' => $this->generateUrl('extend_loan', ['loanRowId' => $loanRowId])
            ]);

            $formView = $form->createView();
        }

        // Convert links into links
        $url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
        $fieldsToConvert = ['Description', 'CareInformation', 'ComponentInformation'];
        foreach ($fieldsToConvert AS $f) {
            $getter = 'get'.$f;
            $setter = 'set'.$f;
            $string = $product->$getter();
            $string = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $string);
            $product->$setter($string);
        }

        // An item in repair will prevent members (but not admins) from reserving
        // The exception is 'on loan' which does allow people to reserve in the future
        if ($product->getInventoryLocation()) {
            // Items without a location are kits, and we use the components to determine availability
            if (!$product->getInventoryLocation()->getIsAvailable() && $product->getInventoryLocation()->getId() != 1) {
                $product->setIsReservable(false);
            }
        }

        // Empty kits cannot be added to basket
        if ($product->getItemType() == InventoryItem::TYPE_KIT && count($product->getComponents()) == 0) {
            $this->addFlash('error', "This kit has no components; please edit and add components.");
            $product->setIsReservable(false);
        }

        // If member has reached maximum reservations
        $maxReservations = $settingsService->getSettingValue('max_reservations');
        if ($maxReservations === "" || $maxReservations == null) {
            // no limit
        } else if ($contact) {
            $reservations = 0;
            if (count($contact->getLoans()) > 0) {
                foreach ($contact->getLoans() AS $loan) {
                    if ($loan->getStatus() == Loan::STATUS_RESERVED) {
                        $reservations++;
                    }
                }
            }
            if ($reservations >= (int)$maxReservations) {
                if ($maxReservations > 0) {
                    $this->addFlash('error', "You already have {$reservations} reservation(s). Maximum reservations per member is {$maxReservations}.");
                }
                $product->setIsReservable(false);
            }
        }

        $maintenanceOverdue = false;
        /** @var \AppBundle\Entity\Maintenance $maintenance */
        foreach ($product->getMaintenances() AS $maintenance) {
            if ($maintenance->getStatus() == Maintenance::STATUS_OVERDUE && $maintenance->getMaintenancePlan()->getPreventBorrowsIfOverdue()) {
                $maintenanceOverdue = true;
            }
        }

        // Already in the basket
        $basketService = $this->get('service.basket');
        if ($basket = $basketService->getBasket()) {

            foreach ($basket->getLoanRows() as $row) {

                if ($row->getInventoryItem()->getId() === $product->getId()) {
                    $product->setInBasket(true);
                    $product->setIsReservable(false);
                }

            }

        }

        return $this->render($template, array(
            'product' => $product,
            'inventory' => $itemService->getInventory($product),
            'user' => $contact,
            'similarItemCount' => count($items),
            'contactBalance' => $contactBalance,
            'isOnWaitingList' => $isOnWaitingList,
            'dailyFee' => (float)$dailyFee,
            'itemFee' => (float)$itemFee,
            'itemLoanDays' => $loanPeriod, // borrows must be in this multiple, usually 1 day
            'maxLoanDays' => $maxLoanDays,
            'minLoanDays' => $minLoanDays,
            'reservationFee' => $reservationFee,
            'isMultiSite' => $isMultiSite,
            'sites' => $sites,

            'maintenanceOverdue' => $maintenanceOverdue,

            // When extending a loan we need the existing times to check with
            'loanId' => $loanId,
            'loanStartAt' => $loanStartAt,
            'itemDueInAt' => $itemDueInAt,

            'pageTitle' => 'Borrow '.$product->getName(),

            // Used for admins to choose 'today' when reserving (time is overridden when calendar loads):
            'currentPickupTime' => $pickupTime->format("Y-m-d 09:00:00"),
            'currentPickupSiteId' => $site->getId(),
            'currentPickupSiteName' => $site->getName(),

            // Extension form is rendered with a form type to include payment_core
            'form' => $formView
        ));
    }
}
