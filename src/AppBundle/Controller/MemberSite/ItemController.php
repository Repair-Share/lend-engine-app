<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
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

        if ($this->get('settings')->getSettingValue('site_is_private')
            && !$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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

        /** @var $product \AppBundle\Entity\InventoryItem */
        if (!$product = $repo->find($productId)) {
            $this->addFlash("error", "Item with ID {$productId} not found.");
            return $this->redirectToRoute('home');
        }

        // If browsing as someone else, use their prices
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $this->getUser()->getId() != $sessionUserId) {
            $contact = $contactRepo->find($sessionUserId);
        } else {
            $contact = $this->getUser();
        }

        $defaultLoanDays = (int)$this->get('settings')->getSettingValue('default_loan_days');
        $minLoanDays = (int)$this->get('settings')->getSettingValue('min_loan_days');
        $maxLoanDays = (int)$this->get('settings')->getSettingValue('max_loan_days');

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
        $dailyFee = round($itemFee / $itemLoanDays, 6);

        // Multiply out for the UI, if items are loaned per-period
        if (1) {
            $itemFee = round($itemFee, 2);
        } else {
            if ($minLoanDays > $itemLoanDays) {
                $itemFee = $itemFee * $minLoanDays;
                $itemLoanDays = $itemLoanDays * $minLoanDays;
            }
        }

        $product->setLoanFee($itemFee);
        $product->setMaxLoanDays($itemLoanDays);

        if ($product->getImageName()) {
            $account_code   = $this->get('service.tenant')->getAccountCode();
            $s3Bucket       = $this->get('service.tenant')->getS3Bucket();
            $product->setImagePath($s3Bucket.$account_code.'/large/'.$product->getImageName());
        }

        $isMultiSite = $this->get('settings')->getSettingValue('multi_site');

        $contactBalance = 0;
        if ($contact) {
            $contactBalance = $contact->getBalance();
        }

        $reservationFee = $this->get('settings')->getSettingValue('reservation_fee');

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

        if ($request->get('modal') == 'true') {
            $template = 'shared/modals/item.html.twig';
        } else {
            $template = 'member_site/pages/item.html.twig';
        }

        if ($loanRowId = $request->get('extend')) {

            /** @var \AppBundle\Services\StripeHandler $stripeService */
            $stripeService = $this->get('service.stripe');

            /** @var \AppBundle\Entity\LoanRow $loanRow */
            $loanRow = $em->getRepository('AppBundle:LoanRow')->find($loanRowId);
            $contact = $loanRow->getLoan()->getContact();

            $stripeUseSavedCards = $this->get('settings')->getSettingValue('stripe_use_saved_cards');

            // Get existing cards for a customer
            $customerStripeId = $contact->getStripeCustomerId();
            if ($customerStripeId && $stripeUseSavedCards) {
                // retrieve their cards
                $stripeCustomer = $stripeService->getCustomerById($customerStripeId);
                if (isset($stripeCustomer['sources']['data'])) {
                    foreach($stripeCustomer['sources']['data'] AS $source) {
                        $creditCard = new CreditCard();
                        $creditCard->setLast4($source['last4']);
                        $creditCard->setExpMonth($source['exp_month']);
                        $creditCard->setExpYear($source['exp_year']);
                        $creditCard->setBrand($source['brand']);
                        $creditCard->setCardId($source['id']);
                        $contact->addCreditCard($creditCard);
                    }
                }
            }
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

        return $this->render($template, array(
            'product' => $product,
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
            'allowBorrow' => 1,
            'pageTitle' => 'Borrow '.$product->getName(),

            // Used for admins to choose 'today' when reserving (time is overridden when calendar loads):
            'currentPickupTime' => $pickupTime->format("Y-m-d 09:00:00"),
            'currentPickupSiteId' => $site->getId(),
            'currentPickupSiteName' => $site->getName()
        ));
    }
}
