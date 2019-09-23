<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class LoanSearchController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("loan-search", name="loan_search")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loanSearchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Repository\LoanRepository $loanRepo */
        $loanRepo = $em->getRepository('AppBundle:Loan');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        // Who are we
        $user = $this->getUser();
        $sessionUserId = $this->get('session')->get('sessionUserId');
        if ($sessionUserId && $user->getId() != $sessionUserId) {
            $user = $contactRepo->find($sessionUserId);
        }

        // Modify times to match local time for calendar
//        $tz = $settingsService->getSettingValue('org_timezone');
//        $timeZone = new \DateTimeZone($tz);

        // Get the data
        $loans = [];
        if ($string = $request->get('loan-search')) {
            $filter = [
                'search' => $string
            ];
            $searchResults = $loanRepo->findLoans(0, 100, $filter, null);

            foreach ($searchResults['data'] AS $loan) {
                /** @var $loan \AppBundle\Entity\Loan */
                $loans[] = $loan;
            }
        }

        return $this->render('member_site/pages/loan_search.html.twig', [
            'user'  => $user,
            'loans' => $loans
        ]);
    }

}