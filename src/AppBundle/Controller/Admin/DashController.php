<?php

namespace AppBundle\Controller\Admin;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DashController extends Controller
{
    /**
     * @Route("admin/", name="homepage")
     */
    public function dashboardAction(Request $request)
    {
        /** START UPDATE OF CORE */

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');
        $tenant = $settingsService->getTenant(false);

        // Update Core (_core DB)
        $settingsService->updateCore($tenant->getStub());

        /** END UPDATE OF CORE */

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $this->getDoctrine()->getRepository('AppBundle:Contact');
        $countContacts = $contactRepo->countAllMembers();

        /** @var \AppBundle\Services\Loan\LoanService $loanService */
        $loanService = $this->get('service.loan');

        $loansByStatus = [
            'pending'   => $loanService->countLoans('PENDING'),
            'active'    => $loanService->countLoans('ACTIVE'),
            'overdue'   => $loanService->countLoans('OVERDUE'),
            //'closed'    => $loanService->countLoans('CLOSED'),
            'reserved'  => $loanService->countLoans('RESERVED'),
        ];

        $contactLocations = [];
        $organisationCountry = $this->get('settings')->getSettingValue('org_country');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $this->getDoctrine()->getRepository('AppBundle:Contact');
        $contacts = $contactRepo->findAll();
        foreach ($contacts AS $contact) {
            /** @var \AppBundle\Entity\Contact $contact */
            if ($contact->getLatitude() && $contact->getLongitude() && $contact->getCountryIsoCode() == $organisationCountry) {
                $contactLocations[] = [
                    'lat' => $contact->getLatitude(),
                    'lng' => $contact->getLongitude()
                ];
            }
        }

        // Get data for the dashboard charts

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');
        $contactsAdded = $contactService->contactsAddedByMonth();

        /** @var \AppBundle\Services\Membership\MembershipService $membershipService */
        $membershipService = $this->get('service.membership');
        $membershipsAdded = $membershipService->membershipsAddedByMonth();

        /** @var \AppBundle\Services\Item\ItemService $itemService */
        $itemService = $this->get('service.item');
        $itemsAdded = $itemService->itemsAddedByMonth();

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');
        $membershipFees = $paymentService->paymentsByMonth('memberships');
        $eventFees      = $paymentService->paymentsByMonth('events');
        $otherFees      = $paymentService->paymentsByMonth('other');

        $loansAdded = $loanService->loansAddedByMonth();

        $membersAddedByMonth = [];
        $itemsAddedByMonth   = [];
        $membershipsAddedByMonth = [];
        $loansAddedByMonth = [];
        $labels  = [];

        $memberGrowth = [];
        $membershipsGrowth = [];
        $itemsGrowth = [];

        $membershipFeesByMonth = [];
        $eventFeesByMonth = [];
        $otherFeesByMonth = [];

        $chartMonths = 6;
        $keys = [];
        $dateKey = new \DateTime();
        $dateKey->modify("-{$chartMonths} months");

        $chartStartDateMonths = $chartMonths - 1;
        $chartStartDate = new \DateTime();
        $chartStartDate->modify("-{$chartStartDateMonths} months");

        for ($n=0; $n<$chartMonths; $n++) {
            $dateKey->modify("+1 month");
            $keys[] = $dateKey->format("Y-m");
        }

        $totalMembers     = $contactService->countAllContacts($chartStartDate);
        $totalItems       = $itemService->countAllItems($chartStartDate);
        $totalMemberships = $membershipService->countMemberships($chartStartDate);

        foreach ($keys AS $key) {
            // Contacts
            if (!isset($contactsAdded[$key])) {
                $contactsAdded[$key] = 0;
            }
            $membersAddedByMonth[] = $contactsAdded[$key];
            $totalMembers += $contactsAdded[$key];
            $memberGrowth[] = $totalMembers;

            // Items
            if (!isset($itemsAdded[$key])) {
                $itemsAdded[$key] = 0;
            }
            $itemsAddedByMonth[] = $itemsAdded[$key];
            $totalItems += $itemsAdded[$key];
            $itemsGrowth[] = $totalItems;

            // Memberships
            if (!isset($membershipsAdded[$key])) {
                $membershipsAdded[$key] = 0;
            }
            $membershipsAddedByMonth[] = $membershipsAdded[$key];
            $totalMemberships += $membershipsAdded[$key];
            $membershipsGrowth[] = $totalMemberships;

            // Loans
            if (!isset($loansAdded[$key])) {
                $loansAdded[$key] = 0;
            }
            $loansAddedByMonth[] = $loansAdded[$key];

            // Membership Fees
            if (!isset($membershipFees[$key])) {
                $membershipFees[$key] = 0;
            }
            $membershipFeesByMonth[] = $membershipFees[$key];

            // Event Fees
            if (!isset($eventFees[$key])) {
                $eventFees[$key] = 0;
            }
            $eventFeesByMonth[] = $eventFees[$key];

            // Other fees
            if (!isset($otherFees[$key])) {
                $otherFees[$key] = 0;
            }
            $otherFeesByMonth[] = $otherFees[$key];

            $date = new \DateTime($key.'-01');
            $labels[] = '"'.$date->format("M Y").'"';
        }

        $isMultiSite = $this->get('settings')->getSettingValue('multi_site');
        $activeSite = $this->getUser()->getActiveSite();

        return $this->render('default/dashboard.html.twig', array(
            'base_dir' => realpath($this->container->getParameter('kernel.root_dir').'/..'),
            'dashData' => [
                'contacts' => $countContacts,
                'members' => $countContacts,
                'loansByStatus' => $loansByStatus
            ],
            'contactLocations' => $contactLocations,
            'labels' => implode(',', $labels),
            'itemsAddedByMonth' => implode(',', $itemsAddedByMonth),
            'itemsGrowth' => implode(',', $itemsGrowth),
            'loansAddedByMonth' => implode(',', $loansAddedByMonth),
            'contactsAddedByMonth' => implode(',', $membersAddedByMonth),
            'contactsGrowth' => implode(',', $memberGrowth),
            'membershipsAddedByMonth' => implode(',', $membershipsAddedByMonth),
            'membershipsGrowth' => implode(',', $membershipsGrowth),
            'membershipFeesByMonth' => implode(',', $membershipFeesByMonth),
            'eventFeesByMonth' => implode(',', $eventFeesByMonth),
            'otherFeesByMonth' => implode(',', $otherFeesByMonth),
            'isMultiSite' => $isMultiSite,
            'activeSite' => $activeSite,
            'apiKey' => base64_encode(getenv('GOOGLE_MAPS_API_KEY_JS'))
        ));
    }
}