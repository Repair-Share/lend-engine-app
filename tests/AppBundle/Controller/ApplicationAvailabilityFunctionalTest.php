<?php

// Tests/Functional/AppBundle/ApplicationAvailabilityFunctionalTest.php
/**
 *
 * A simple but useful file to ensure that all non-entity-specific pages are showing a successful response
 *
 */

namespace Tests\Functional\AppBundle;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ApplicationAvailabilityFunctionalTest extends AuthenticatedControllerTest
{
    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful() || $this->client->getResponse()->isRedirection());
    }

    /**
     * @return array
     */
    public function urlProvider()
    {
        return [
            // Admin : settings
            ['/admin/settings/general'],
            ['/admin/billing'],
            ['/admin/settings/reservations'],
            ['/admin/settings/member_site'],
            ['/admin/settings/templates'],
            ['/admin/settings/events'],
            ['/admin/settings/labels'],
            ['/admin/settings/maintenance-plans'],

            ['/admin/site/list'],
            ['/admin/site'],
            ['/admin/site/1'],
            ['/admin/site/1/event'],

            ['/admin/location/list'],
            ['/admin/location'],

            ['/admin/page/list'],

            // Maintenance schedule
            ['/admin/maintenance/list'],

            // Users
            ['/admin/users/list'],
            ['/admin/user'],

            ['/admin/category/list'],
            ['/admin/category'],

            ['/admin/section/list'],
            ['/admin/section'],

            ['/admin/productField/list'],
            ['/admin/productField'],

            ['/admin/itemCondition/list'],
            ['/admin/itemCondition'],

            ['/admin/settings/labels'],

            ['/admin/checkInPrompt/list'],
            ['/admin/checkInPrompt'],

            ['/admin/checkOutPrompt/list'],
            ['/admin/checkOutPrompt'],

            ['/admin/import/contacts/'],

            ['/admin/contactField/list'],
            ['/admin/contactField'],

            ['/admin/membershipType/list'],
            ['/admin/membershipType'],

            ['/admin/payment-method/list'],
            ['/admin/payment-method'],

            // Admin : other
            ['/admin/'],
            ['/admin/loan/list'],
            ['/admin/item/list'],
            ['/admin/item_sector'],
            ['/admin/contact/list'],

            // Reports
            ['/admin/report/report_loans'],
            ['/admin/report/report_items'],
            ['/admin/report/report_items?group_by=item'],
            ['/admin/report/report_items?group_by=item_name'],
            ['/admin/report/all_items'],
            ['/admin/report/non_loaned_items'],
            ['/admin/report/report_payments'],
            ['/admin/report/report_memberships'],

            // Admin datatables JSON
            ['/admin/dt/loan/list'],
            ['/admin/dt/item/list'],
            ['/admin/dt/contact/list'],
            ['/admin/dt/maintenance/list'],

            // Admin events
            ['/admin/event/list'],
            ['/admin/event'],

            // FOS user bundle pages
            ['/login'],
            ['/profile/'],
            ['/profile/edit'],
            ['/profile/change-password'],

            // Member site
            ['/products?show=recent'],
            ['/register'],
            ['/choose_membership'],
            ['/sites'],
            ['/member/loans'],
            ['/member/payments'],
            ['/member/add-credit'],
            ['/member/my-events'],
            ['/loan-search'],
            ['/member-search?go=&member-search=test'],
            ['/products?search=test'],
            ['/site-data?itemId=1000'],

            // Email testing
            ['/admin/settings/email/test?template=welcome'],
            ['/admin/settings/email/test?template=reminder'],
            ['/admin/settings/email/test?template=checkout'],
            ['/admin/settings/email/test?template=overdue'],
            ['/admin/settings/email/test?template=extend'],
            ['/admin/settings/email/test?template=reserve'],
            ['/admin/settings/email/test?template=checkin'],
            ['/admin/settings/email/test?template=expired'],
            ['/admin/settings/email/test?template=donor_notification'],

            // Events
            ['/events'],
            ['/events/json'],
        ];
    }
}