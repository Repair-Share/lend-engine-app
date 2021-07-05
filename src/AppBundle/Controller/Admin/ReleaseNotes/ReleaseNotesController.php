<?php

namespace AppBundle\Controller\Admin\ReleaseNotes;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class ReleaseNotesController extends Controller
{
    /**
     * @Route("admin/release_notes", name="releaseNotes")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function releaseNotesAction(Request $request)
    {
        $gitHubIssueLink = 'https://github.com/lend-engine/lend-engine-app/issues/';

        $bugfixes = [
            '129' => 'A non-reservable item can be reserved by clicking on the empty calendar',
            '120' => 'User can extend an loan over existing reservations',
            '111' => 'Update Welsh language after translation',
            '128' => 'Loans do not adhere to conditions set out in opening hours and will set to opening time instead of closing time.',
            '127' => 'Booking item allows return on a closed day',
            '123' => 'As a user you can check-in on an event that is not self serve "book now" is not available',
            '117' => 'Setting Minimum Loan Period to 14 days causes problems with item loan periods on catalogue view',
            '118' => 'Exception: DateTime::__construct(): (Rollbar 107)',
            '116' => 'Custom opening hours not functioning',
            '105' => 'Events don\'t adhere to the maximum attendees',
            '110' => 'Add Catalan language',
            '109' => 'Update language Slovenščina',
            '101' => 'Exception: DateTime::__construct(): (Rollbar 101)',
            '99'  => 'Exception: DateTime::__construct(): (Rollbar 98)',
            '97'  => 'Add a release notes page to admin',
            '96'  => 'Incorrect hours being sent in emails (with unit tests)'
        ];

        return $this->render('release_notes/notes.html.twig', [
            'bugfixes'  => $bugfixes,
            'issueLink' => $gitHubIssueLink
        ]);
    }

}