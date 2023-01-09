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
            '195' => 'Refund in Stripe should allow optional reduction in credit back on the account, default should be no change.',
            '220' => 'Repeating custom hours, span a period of days',
            '219' => 'Pagination on both Members and Reports_Memberships',
            '216' => 'CSV Exports are still not matching the data if you compare the report to the dashboard for one account',
            '199' => 'Overdue item did not appear on overdue list',
            '212' => 'Downloading PDF issue on a mobile device',
            '208' => 'Flag user account with message and prevent borrowing until its cleared',
            '207' => 'Reduce fake accounts with a captcha on the sign up page',
            '206' => 'Rollbar 155: Failed to parse time string',
            '205' => 'Rollbar 201: setContact() must be an instance of AppBundle\Entity\Contact',
            '204' => 'Update the French language translation',
            '202' => 'GDPR issue, able to see the name of someone who has an item booked without being admin or staff, also a standard user can see checkout prompts which are for admin/staff only.',
            '141' => 'Pagination and count on Members report page',
            '201' => "Loan can't checkout because 'Shipping' is reserved",
            '197' => 'Able to add a loan item to a loan that is already checked out',
            '198' => 'Rollbar #586: update org name error',
            '194' => 'Basket fails to open loan showing "no basket found"',
            '196' => 'Rollbar #171',
            '191' => 'Update jQuery to latest version',
            '188' => 'Loan counter values at the top bar don\'t match with the loan\'s list filtering',
            '187' => 'Email wording changes',
            '185' => 'Rollbar 184: Call to a member function getSite() on null',
            '183' => 'Check in email does not translate title or subject (Dutch)',
            '182' => 'Update the French language file',
            '181' => 'Live Stripe.js integrations must use HTTPS',
            '180' => 'JavaScript number format causes JS syntax error',
            '178' => 'Increase the max number of events to 250 for the plus plan',
            '169' => 'Changeover time on site causes problems when extending a loan with an existing booking',
            '168' => 'New Search capability not working for everyone',
            '161' => 'Dutch Language had an error with a variable',
            '150' => 'Request to complete two languages Slovak (update) and Ukrainian (new)',
            '148' => 'Unable to add a loan item to existing loan with adjacent booking',
            '139' => 'Search box does not return results for multiple words',
            '133' => 'Order of Loan lists need changing',
            '131' => 'Maintenance change date & location at once bug',
            '140' => 'Wrong basket time when lending a kit',
            '138' => 'Calendar allowed returning date despite the site is closed',
            '137' => 'Cannot auto-set end date as there is no opening hour slot for T+4',
            '134' => 'Start time is not within opening hours issue',
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
