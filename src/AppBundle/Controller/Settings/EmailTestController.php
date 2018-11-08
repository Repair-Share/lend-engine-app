<?php

namespace AppBundle\Controller\Settings;

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

        $client = new PostmarkClient($this->getParameter('postmark_api_key'));

        $em = $this->getDoctrine()->getManager();

        $senderName  = $this->get('tenant_information')->getCompanyName();
        $senderEmail = $this->get('tenant_information')->getCompanyEmail();
        $accountCode = $this->get('tenant_information')->getAccountCode();

        $locale = $this->get('tenant_information')->getLocale();
        $user = $this->getUser();

        $repo = $em->getRepository('AppBundle:Account');
        $tenant = $repo->findOneBy(['stub' => $accountCode]);

        $subject = '';
        $message = '';

        switch ($request->get('template')) {

            case "welcome":

                if (!$subject = $this->get('settings')->getSettingValue('email_welcome_subject')) {
                    $subject = "Your login details for " . $this->get('tenant_information')->getCompanyName();
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

        try {

            $toEmailAddress = $user->getEmail();
            if ($toEmailAddress == 'admin@email.com') {
                $toEmailAddress = 'hello@lend-engine.com';
            }

            $client->sendEmail(
                "{$senderName} <hello@lend-engine.com>",
                $toEmailAddress,
                $subject,
                $message,
                null,
                null,
                true,
                $senderEmail
            );

            $this->addFlash('success', "Sent a test email to ".$user->getEmail());

        } catch (\Exception $e) {

            $this->addFlash('debug', $e->getMessage());
            $this->addFlash('error', 'Failed to send email to '.$user->getEmail());

        }

        return $this->redirectToRoute('settings_templates');

    }

}