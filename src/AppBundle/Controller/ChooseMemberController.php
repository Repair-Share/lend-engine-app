<?php

namespace AppBundle\Controller;

use AppBundle\Entity\WaitingListItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChooseMemberController extends Controller
{

    /**
     * When adding items to waiting list, modal
     * @Route("admin/choose_member", name="choose_member")
     */
    public function chooseContactAction(Request $request)
    {
        return $this->render(
            'modals/choose_member.html.twig',
            array(
                'title' => 'Choose a member',
                'subTitle' => ''
            )
        );
    }

    /**
     * JSON responder for member search when adding items to waiting list
     * @Route("admin/member/search", name="search_member")
     */
    public function searchMember(Request $request)
    {
        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $search = $request->get('q');
        $filter = array(
            'search' => $search
        );
        $searchResults = $contactService->contactSearch(0, 100, $filter);
        $contacts = $searchResults['data'];

        $reserveItemId = (int)$request->get('reserveItemId');
        $waitListItemId = (int)$request->get('waitListItemId');

        $data = array();
        foreach ($contacts AS $contact) {
            $data[] = $contact;
        }
        return $this->render(
            'contact/member_search_results.html.twig',
            array(
                'data' => $data,
                'loanId' => $request->get('loanId'),
                'reserveItemId' => $reserveItemId,
                'waitListItemId' => $waitListItemId
            )
        );
    }

}