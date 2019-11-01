<?php

namespace AppBundle\Controller\Admin\Contact;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ContactSearchController extends Controller
{

    /**
     * For Select2 menus
     * @Route("admin/contact/search", name="ajax_contact_search")
     */
    public function contactSearch(Request $request)
    {
        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $data = [];
        $data[] = [
            'id' => 0,
            'text' => 'None'
        ];

        if ($search = $request->get('q')) {
            $filter = ['search' => $search];
            $searchResults = $contactService->contactSearch(0, 20, $filter, []);

            // Returns fully hydrated contacts
            $contacts = $searchResults['data'];

            /** @var \AppBundle\Entity\Contact $contact */
            foreach ($contacts AS $contact) {
                $data[] = [
                    'id' => $contact->getId(),
                    'text' => $contact->getName()
                ];
            }
        }

        return new Response(
            json_encode($data),
            200,
            array('Content-Type' => 'application/json')
        );
    }

}