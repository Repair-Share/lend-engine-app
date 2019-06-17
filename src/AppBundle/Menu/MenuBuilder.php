<?php

// src/AppBundle/Menu/Builder.php
namespace AppBundle\Menu;

use AppBundle\Entity\Loan;
use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class MenuBuilder
{

    private $menu;

    private $container;

    private $tokenStorage;

    /**
     * @param FactoryInterface $factory
     * @param Container $container
     *
     * Add any other dependency you need
     */
    public function __construct(FactoryInterface $factory, Container $container, $tokenStorage)
    {
        $this->factory   = $factory;
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function adminMenu()
    {
        /** @var \AppBundle\Entity\Contact $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $this->menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'sidebar-menu'
            )
        ));

        $this->addMenuItem('Dashboard', 'homepage', 'fa-bar-chart');
        $this->addMenuItem('Member site', 'home', 'fa-window-maximize');

        $this->addMenuItem('Loans', 'loan_list', 'fa-shopping-bag');
        $this->addChildItem('Loans', 'All', 'loan_list', '', '', ['status' => 'ALL']);
        $this->addChildItem('Loans', 'Pending', 'loan_list', '', '', ['status' => 'PENDING']);
        $this->addChildItem('Loans', 'On loan', 'loan_list', '', '', ['status' => 'ACTIVE']);
        $this->addChildItem('Loans', 'Overdue', 'loan_list', '', '', ['status' => 'OVERDUE']);
        $this->addChildItem('Loans', 'Reservations', 'loan_list', '', '', ['status' => Loan::STATUS_RESERVED]);

        $this->addMenuItem('Items', 'null', 'fa-cubes');
        $this->addChildItem('Items', 'Browse items', 'item_list', '', '', []);

        $this->addChildItem('Items', 'Assigned to me', 'item_list', '', '', ['filterAssignedTo' => $user->getId()]);

        if ( $this->container->get('security.authorization_checker')->isGranted("ROLE_SUPER_USER") ) {
            $this->addChildItem('Items', 'Bulk update <sup>beta</sup>', 'import_items', '');
        }
        $this->addChildItem('Items', 'Add item', 'item_type', '');

        if ($this->container->get('settings')->getSettingValue('enable_waiting_list')) {
            $this->addChildItem('Items', 'Waiting list', 'item_waiting_list', '');
        }

        $this->addMenuItem('Contacts / Members', 'contact_list', 'fa-group');

        $this->addMenuItem('Events <span class="label bg-orange">NEW</span>', 'admin_event_list', 'fa-calendar');

        $this->addMenuItem('Reports', 'null', 'fa-signal');
        $this->addChildItem('Reports', 'Loans by status/member', 'report_loans', '');
        $this->addChildItem('Reports', 'Loans by item', 'report_items', '');
        $this->addChildItem('Reports', 'Loan item detail', 'report_all_items', '');
        $this->addChildItem('Reports', 'Non-loaned items', 'non_loaned_items', '');
        $this->addChildItem('Reports', 'Payments', 'report_payments', '');
        $this->addChildItem('Reports', 'Item costs', 'report_costs', '');
        $this->addChildItem('Reports', 'Memberships', 'membership_list', '');

        if ($this->container->get('service.tenant')->getIndustry() == "toys") {
            $this->addChildItem('Reports', 'Children', 'report_children', '');
        }

        if ( $this->container->get('security.authorization_checker')->isGranted("ROLE_SUPER_USER") ) {
            $this->addMenuItem('Settings', 'settings', 'fa-cogs');
        }

        return $this->menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function settingsMenu()
    {
        $this->menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav settings-nav'
            )
        ));

        $this->menu->addChild('General settings', array('route' => 'settings'));
        $this->menu->addChild('Billing', array('route' => 'billing'));
        $this->menu->addChild('Sites & opening hours', array('route' => 'site_list'));
        $this->menu->addChild('Locations', array('route' => 'location_list'));

        $this->menu->addChild('Loans & Reservations', array('route' => 'settings_reservations'));
        $this->menu->addChild('Member site', array('route' => 'settings_member_site'));
        $this->menu->addChild('Site pages & links', array('route' => 'page_list'));

        if ($this->container->get('service.tenant')->getFeature('CustomEmail')) {
            $this->menu->addChild('Email templates', array('route' => 'settings_templates'));
        }
        $this->menu->addChild('Staff / team', array('route' => 'users_list'));

        $this->menu->addChild('Item categories', array('route' => 'tags_list'));
        $this->menu->addChild('Item fields', array('route' => 'product_field_list'));
        $this->menu->addChild('Item conditions', array('route' => 'itemCondition_list'));
        $this->menu->addChild('Item barcode labels', array('route' => 'settings_labels'));

        $this->menu->addChild('Check in prompts', array('route' => 'checkInPrompt_list'));
        $this->menu->addChild('Check out prompts', array('route' => 'checkOutPrompt_list'));

        $this->menu->addChild('Import contacts', array('route' => 'import_contacts'));

        $this->menu->addChild('Contact fields', array('route' => 'contact_field_list'));
        $this->menu->addChild('Membership types', array('route' => 'membershipType_list'));
        $this->menu->addChild('Payment methods', array('route' => 'payment_method_list'));

        return $this->menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function itemsMenuStacked()
    {
        return $this->itemsMenu(true);
    }

    /**
     * @param bool $stacked
     * @return \Knp\Menu\ItemInterface
     * @throws \Exception
     */
    public function itemsMenu($stacked = false)
    {

        if ($stacked == true) {
            $class = 'nav nav-pills nav-stacked items-nav';
        } else {
            $class = 'nav nav-pills items-nav';
        }

        $requestStack = $this->container->get('request_stack');
        $request = $requestStack->getCurrentRequest();

        $this->menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => $class
            )
        ));

        if ($this->container->get('settings')->getSettingValue('site_is_private')
            && !$this->container->get('security.authorization_checker')->isGranted('ROLE_USER')) {

            $txt = "";
            $this->menu->addChild($txt, array(
                'route' => '',
                'extras' => array('safe_label' => true)
            ));

            return $this->menu;
        }

        // Show recently added items
        $parameters = [
            'show' => 'recent',
            'locationId' => $request->get('locationId'),
            'e' => $request->get('e') // embed
        ];
        if ($request->get('show') == 'recent') {
            $class = 'active';
        } else {
            $class = '';
        }
        $this->menu->addChild($this->container->get('translator')->trans("public_misc.link_recent_items", [], 'member_site'), array(
            'route' => 'public_products',
            'routeParameters' => $parameters,
            'extras' => array('safe_label' => true)
        ))->setAttribute('class', 'recent-items '. $class);

        // Product pages by tag --------------

        /** @var $repo \AppBundle\Repository\ProductTagRepository */
        $repo = $this->container->get('doctrine')->getRepository('AppBundle:ProductTag');
        $tags = $repo->findAllOrderedBySort();

        foreach ($tags AS $tag) {

            /** @var $tag \AppBundle\Entity\ProductTag */

            if ($tag->getShowOnWebsite() != true) {
                continue;
            }

            $parameters = [
                'tagId' => $tag->getId(),
                'locationId' => $request->get('locationId'),
                'e' => $request->get('e') // embed
            ];

            if ($tag->getId() == $request->get('tagId')) {
                $class = 'active';
            } else {
                $class = '';
            }

            $this->menu->addChild($tag->getName(), array(
                'route' => 'public_products',
                'routeParameters' => $parameters,
                'class' => $class,
                'label' => $tag->getName(),
                'extras' => array('safe_label' => true)
            ))->setAttribute('class', $class);
        }

        return $this->menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function pagesMenuStacked()
    {
        return $this->pagesMenu(true);
    }

    /**
     * @param bool $stacked
     * @return \Knp\Menu\ItemInterface
     * @throws \Exception
     */
    public function pagesMenu($stacked = false)
    {

        $requestStack = $this->container->get('request_stack');
        $request = $requestStack->getCurrentRequest();

        if ($stacked == true) {
            $class = 'nav nav-pills nav-stacked custom-nav';
        } else {
            $class = 'nav nav-pills custom-nav';
        }

        $this->menu = $this->factory->createItem('root', [

            'childrenAttributes' => [
                'class' => $class,
                'id' => 'CustomPagesNav'
            ]
        ]);

        /** @var $repo \AppBundle\Repository\PageRepository */
        $repo = $this->container->get('doctrine')->getRepository('AppBundle:Page');
        $pages = $repo->findOrderedBySort();

        $n = 0;
        foreach ($pages AS $page) {

            $n++;

            // Don't show any pages that may have been created when user was on free trial
            if (!$this->container->get('service.tenant')->getFeature("Page") && $n > 1) {
                continue;
            }

            /** @var $page \AppBundle\Entity\Page */

            $icon = '<i class="fa fa-bars site-editable"></i>';

            if ($this->container->get('security.authorization_checker')->isGranted("ROLE_ADMIN")) {
                // Show all pages including hidden ones
            } else if ($this->container->get('security.authorization_checker')->isGranted("ROLE_USER")) {
                // Show member-only pages and public pages
                if ($page->getVisibility() == "ADMIN" || $page->getVisibility() == "HIDDEN") {
                    continue;
                }
            } else {
                // Show public only pages
                if ($page->getVisibility() != "PUBLIC" || $page->getVisibility() == "HIDDEN") {
                    continue;
                }
            }

            $parameters = [
                'pageId' => $page->getId()
            ];

            if ($page->getId() == $request->get('pageId')) {
                $class = 'active';
            } else {
                $class = '';
            }

            if ($page->getVisibility() == "HIDDEN" && $this->container->get('security.authorization_checker')->isGranted("ROLE_ADMIN")) {
                $class .= ' page-hidden site-editable';
            }

            if ($url = $page->getUrl()) {

                if ($this->container->get('service.tenant')->getIsEditMode()) {

                    $this->menu->addChild($page->getName(), array(
                        'route' => 'public_page_edit',
                        'routeParameters' => $parameters,
                        'class' => $class,
                        'label' => $icon.$page->getName(),
                        'extras' => array('safe_label' => true)
                    ))->setAttribute('class', $class)
                        ->setAttribute('id', 'page_'.$page->getId());

                } else {

                    if (strstr($url, 'http')) {
                        $target = '_blank';
                        $linkIcon = ' <i class="fa fa-external-link-alt" style="font-size: 10px; color: #aaa; "></i>';
                    } else {
                        $target = '_top';
                        $linkIcon = '';
                    }

                    $this->menu->addChild($page->getName(), array(
                        'uri' => $url,
                        'class' => $class,
                        'label' => $icon.$page->getName().$linkIcon,
                        'extras' => array('safe_label' => true)
                    ))->setAttribute('class', $class)
                        ->setAttribute('id', 'page_'.$page->getId())
                        ->setLinkAttributes(array('target' => $target));

                }

            } else {

                $linkIcon = '';
                if ($page->getVisibility() == "ADMIN") {
                    $linkIcon = ' <i class="fa fa-user-cog site-editable" style="font-size: 10px; color: #d4302d; "></i>';
                } else if ($page->getVisibility() == "MEMBERS") {
                    $linkIcon = ' <i class="fa fa-users site-editable" style="font-size: 10px; color: #d4302d; "></i>';
                }

                $params = [
                    'pageId' => $page->getId(),
                    'slug'   => $page->getSlug()
                ];

                $this->menu->addChild($page->getName(), array(
                    'route' => 'public_page_by_slug',
                    'routeParameters' => $params,
                    'class' => $class,
                    'label' => $icon.$page->getName().$linkIcon,
                    'extras' => array('safe_label' => true)
                ))->setAttribute('class', $class)
                    ->setAttribute('id', 'page_'.$page->getId());

            }

        }

        // an extra one to add a new page
        $this->menu->addChild("Add a new page", array(
            'route' => 'public_page_edit',
            'routeParameters' => ['pageId' => "new"],
            'class' => $class,
            'label' => '<i class="fa fa-plus site-editable"></i> Add new page/link',
            'extras' => array('safe_label' => true)
        ))->setAttribute('class', "site-editable")
            ->setAttribute('id', 'page_new');

        return $this->menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function accountMenu()
    {
        $this->menu = $this->factory->createItem('root', array(
            'childrenAttributes' => array(
                'class' => 'nav nav-pills'
            )
        ));

        if ( $this->container->get('security.authorization_checker')->isGranted("ROLE_ADMIN") ) {

        } else {
            $profileLabel = $this->container->get('translator')->trans("public_user_menu.contact_info", [], 'member_site');
            $this->menu->addChild($profileLabel, array('route' => 'fos_user_profile_show'));
        }

        $myAccountText = $this->container->get('translator')->trans("My account", [], 'member_site');
        $this->menu->addChild($myAccountText, array('route' => 'fos_user_profile_show'));

        $loanText = $this->container->get('translator')->trans("Loans", [], 'member_site');
        $this->menu->addChild($loanText, array('route' => 'loans'));

        $this->menu->addChild($this->container->get('translator')->trans("Payments", [], 'member_site'), array('route' => 'payments'));

        return $this->menu;
    }

    /**
     * @param $label
     * @param $route
     * @param string $icon
     */
    private function addMenuItem($label, $route, $icon = '')
    {
        // Add menu tags:
        // <small class="label pull-right bg-green">new</small>
        $this->menu->addChild($label, array(
            'route' => $route,
            'class' => 'treeview',
            'childrenAttributes' => array('class' => 'treeview-menu',),
            'label' => '<i class="fa '.$icon.'"></i> <span> '.$label.'</span>',
            'extras' => array('safe_label' => true)
        ));
    }

    /**
     * @param $parent
     * @param $label
     * @param $route
     * @param string $icon
     * @param string $tag
     * @param array $routeParameters
     */
    private function addChildItem($parent, $label, $route, $icon = '', $tag = '', $routeParameters = array())
    {
        $this->menu[$parent]->addChild('<i class="fa '.$icon.'"></i> <span>'.$label.'</span>'.$tag,
            array(
                'route' => $route,
                'routeParameters' => $routeParameters,
                'extras' => array('safe_label' => true),
            ));
    }

}