<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\Attendee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\PaymentMethod;
use AppBundle\Entity\Setting;
use AppBundle\Form\Type\SettingsTemplatesType;
use Postmark\PostmarkClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\SettingsType;
use Symfony\Component\HttpFoundation\Session\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;


class EmailTestController extends Controller
{

    /**
     * @Route("admin/settings/email/test", name="settings_email_test")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function emailTestAction(Request $request)
    {
        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $em = $this->getDoctrine()->getManager();

        $accountCode = $this->get('service.tenant')->getAccountCode();

        $locale = $this->get('service.tenant')->getLocale();
        $user = $this->getUser();

        $repo = $em->getRepository('AppBundle:Tenant');
        $tenant = $repo->findOneBy(['stub' => $accountCode]);

        $subject = '';
        $message = '';

        switch ($request->get('template')) {

            case "welcome":

                if (!$subject = $this->get('settings')->getSettingValue('email_welcome_subject')) {
                    $subject = "Your login details for " . $this->get('service.tenant')->getCompanyName();
                }

                $message = $this->renderView(
                    'emails/site_welcome.html.twig',
                    array(
                        'email'    => 'email@email.com',
                        'password' => 'password',
                        'user_locale' => $locale,
                        'tenant'       => $tenant
                    )
                );
                break;

            case "checkin":

                if (!$subject = $this->get('settings')->getSettingValue('email_loan_checkin_subject')) {
                    $subject = "Item has been checked in";
                }

                $row = [
                    'inventoryItem' => [
                        'name' => 'Carpet cleaner',
                        'imageName' => '',
                    ],
                    'loan' => [
                        'contact' => [
                            'name' => 'Emily Parker'
                        ]
                    ],
                    'dueInAt' => new \DateTime(),
                    'dueOutAt' => new \DateTime(),
                    'checkedInAt' => new \DateTime()
                ];

                $message = $this->renderView(
                    'emails/loan_checkin.html.twig',
                    [
                        'row' => $row,
                        'includeButton' => false,
                        'message' => '',
                        'checkedInBy' => "John Doe"
                    ]
                );
                break;

            case "event_booking":

                if (!$subject = $this->get('settings')->getSettingValue('email_booking_confirmation_subject')) {
                    $subject = "Booking confirmation : Test event";
                }

                $d = new \DateTime();
                $event = new Event();
                $event->setTitle("Test event");
                $event->setDate($d);
                $event->setTimeFrom("0900");
                $event->setTimeTo("1100");

                /** @var \AppBundle\Repository\SiteRepository $siteRepo */
                $siteRepo   = $em->getRepository('AppBundle:Site');
                $site = $siteRepo->find(1);
                $event->setSite($site);

                $attendee = new Attendee();
                $attendee->setContact($user);
                $attendee->setPrice(20);
                $attendee->setEvent($event);

                $message = $this->renderView(
                    'emails/booking_confirmation.html.twig',
                    array(
                        'attendee' => $attendee,
                        'message'  => "This is a test email"
                    )
                );
                break;

            case "overdue":

                $rows = [];
                $row = [
                    'inventoryItem' => [
                        'name' => 'Test item name',
                        'imageName' => '',
                    ],
                    'dueInAt' => new \DateTime()
                ];
                $rows[] = $row;

                $message = $this->renderView(
                    'emails/overdue_reminder.html.twig',
                    array(
                        'loanRows' => $rows,
                        'loanId' => 1000,
                        'user_locale' => $locale,
                        'tenant'   => $tenant
                    )
                );

                $subject = "An item is overdue";

                break;

            case "expired":

                $expiredAt = new \DateTime();

                /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
                $membershipTypeRepo   = $em->getRepository('AppBundle:MembershipType');
                $selfServeMemberships = $membershipTypeRepo->findBy(['isSelfServe' => true]);

                // determine whether this account has self-serve memberships
                $canSelfRenew = false;
                if (count($selfServeMemberships) == 1) {
                    $canSelfRenew = true;
                }

                $message = $this->renderView(
                    'emails/membership_expiry.html.twig',
                    array(
                        'expiresAt'    => $expiredAt,
                        'canSelfRenew' => $canSelfRenew,
                        'tenant'       => $tenant
                    )
                );

                $subject = 'Your membership has expired';

                break;

            case "reminder":

                $items = [];
                $item = [
                    'name' => 'First item on the loan',
                    'imageName' => '',
                    'componentInformation' => "Component information",
                    'sites' => [
                        0 => [
                            'name' => 'Site name',
                            'address' => 'Site address',
                            'postCode' => 'Site postcode'
                        ]
                    ]
                ];
                $items[] = $item;

                $message = $this->renderView(
                    'emails/loan_reminder.html.twig',
                    array(
                        'dueDate' => new \DateTime(),
                        'items' => $items,
                        'user_locale' => $locale,
                        'tenant'       => $tenant
                    )
                );

                $subject = 'Your loan {ID here} is due';

                break;

            case "reservation_reminder":

                $rows = [];
                $row = [
                    'inventoryItem' => [
                        'name' => 'Test item name',
                        'imageName' => '',
                    ],
                    'siteFrom' => [
                        'name' => "Pickup site name",
                        'address' => "Pickup site address"
                    ]
                ];
                $rows[] = $row;

                $message = $this->renderView(
                    'emails/reservation_reminder.html.twig',
                    array(
                        'dueDate' => new \DateTime(),
                        'loanRows' => $rows,
                        'loanId' => 1000,
                        'user_locale' => $locale,
                        'tenant'       => $tenant
                    )
                );

                $subject = "Pick up your reservation tomorrow";

                break;

            case "checkout":

                if (!$subject = $this->get('settings')->getSettingValue('email_loan_confirmation_subject')) {
                    $subject = "Your loan information";
                }
                $subject .= " (Ref {ID here})";

                $loanRows = [
                    0 => [
                        'inventoryItem' => [
                            'name' => 'Test item name',
                            'imageName' => '',
                            'description' => 'Test item description',
                            'componentInformation' => 'Test component information',
                            'fileAttachments' => [
                                0 => [
                                    'sendToMemberOnCheckout' => true,
                                    'fileName' => 'TestFileName.pdf'
                                ]
                            ],
                            'sites' => [
                                0 => [
                                    'name' => 'Site name',
                                    'address' => 'Site address',
                                    'postCode' => 'Site postcode'
                                ]
                            ]
                        ],
                        'fee' => 1,
                        'dueInAt' => new \DateTime(),
                        'dueOutAt' => new \DateTime()

                    ]
                ];
                $message = $this->renderView(
                    'emails/loan_checkout.html.twig',
                    array(
                        'loanRows' => $loanRows,
                        'user_locale' => $locale
                    )
                );
                break;

            case "extend":

                if (!$subject = $this->get('settings')->getSettingValue('email_loan_extension_subject')) {
                    $subject = "Your loan return date has been updated";
                }

                $loanRow = [
                    'inventoryItem' => [
                        'name' => 'Test item name',
                        'description' => 'Test item description',
                        'componentInformation' => 'Test component information',
                        'fileAttachments' => [
                            0 => [
                                'sendToMemberOnCheckout' => true,
                                'fileName' => 'TestFileName.pdf'
                            ]
                        ]
                    ],
                    'fee' => 1,
                    'dueInAt' => new \DateTime()
                ];

                $message = $this->renderView(
                    'emails/loan_extend.html.twig',
                    array(
                        'loanRow' => $loanRow,
                        'user_locale' => $locale
                    )
                );

                break;

            case "donor_notification":

                if (!$subject = $this->get('settings')->getSettingValue('email_donor_notification_subject')) {
                    $subject = "Your donated item has been lent out";
                }

                $loanRow = [
                    'inventoryItem' => [
                        'name' => "Example item",
                        'imageName' => ''
                    ]
                ];

                $message = $this->renderView(
                    'emails/loan_donor_notify.html.twig',
                    array(
                        'loanRow' => $loanRow,
                    )
                );

                break;

            case "reserve":

                if (!$subject = $this->get('settings')->getSettingValue('email_reserve_confirmation_subject')) {
                    $subject = "Your reservation confirmation";
                }

                $from = new \DateTime();
                $to = new \DateTime();
                $to->modify("+1 week");

                $loanRows = [
                    0 => [
                        'inventoryItem' => [
                            'name' => 'Test item name',
                            'description' => 'Test item description',
                            'componentInformation' => 'Test component information',
                            'fileAttachments' => [
                                0 => [
                                    'sendToMemberOnCheckout' => true,
                                    'fileName' => 'TestFileName.pdf'
                                ]
                            ]
                        ],
                        'fee' => 1,
                        'dueOutAt' => $from,
                        'dueInAt' => $to
                    ]
                ];
                $message = $this->renderView(
                    'emails/reservation_confirm.html.twig',
                    array(
                        'loanRows' => $loanRows,
                        'message' => '',
                        'user_locale' => $locale,
                        'tenant'       => $tenant
                    )
                );
                $subject .= " (Ref {ID here})";
                break;
        }

        // Send the email
        if ($emailService->send($user->getEmail(), $user->getName(), $subject, $message, false)) {
            $this->addFlash('success', "Sent a test email to ".$user->getEmail());
        } else if ($emailService->getErrors() > 0) {
            foreach ($emailService->getErrors() AS $msg) {
                $this->addFlash('error', $msg);
            }
        }

        if ($request->get('template') == 'event_booking') {
            return $this->redirectToRoute('settings_events');
        } else {
            return $this->redirectToRoute('settings_templates');
        }

    }

}