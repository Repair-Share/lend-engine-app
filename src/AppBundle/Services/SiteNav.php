<?php
namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\RequestStack;

class SiteNav
{
    /** @var EntityManager  */
    private $em;

    /** @var AuthorizationChecker  */
    private $auth;

    /** @var SettingsService */
    private $settings;

    private $translator;

    /** @var Router */
    private $router;

    /** @var RequestStack */
    private $request;

    /** @var string */
    private $menu = '';

    public function __construct(EntityManager $em,
                                AuthorizationChecker $tokenStorage,
                                SettingsService $settingsService,
                                Translator $translator,
                                Router $router,
                                RequestStack $requestStack)
    {
        $this->em = $em;
        $this->auth = $tokenStorage;
        $this->settings = $settingsService;
        $this->translator = $translator;
        $this->router = $router;
        $this->request = $requestStack;
    }

    public function render()
    {
        $request = $this->request->getCurrentRequest();

        if ($this->settings->getSettingValue('site_is_private')
            && !$this->auth->isGranted("ROLE_USER")) {
            return '';
        }

        $this->menu = '<ul class="nav nav-pills nav-stacked items-nav" id="accordion1">';

        // Recent items
        $text = $this->translator->trans("public_misc.link_recent_items", [], 'member_site');
        $uri = $this->router->generate('public_products', [
            'show' => 'recent',
            'locationId' => $request->get('locationId'),
        ]);

        if ($request->get('show') == 'recent') {
            $class = 'active';
        } else {
            $class = '';
        }

        $this->addLink($uri, $text, $class);

        /** @var $repo \AppBundle\Repository\ProductTagRepository */
        $repo = $this->em->getRepository('AppBundle:ProductTag');
        $tags = $repo->findAllOrderedBySort();

        $previousSectionName = null;
        $sectionName = null;
        $sectionId = null;

        $tagsBySectionName = [];
        /** @var $tag \AppBundle\Entity\ProductTag */
        foreach ($tags AS $tag) {
            if ($section = $tag->getSection()) {
                $sectionName = $section->getName();
            } else {
                $sectionName = '-';
            }
            if (!isset($tagsBySectionName[$sectionName])) {
                $tagsBySectionName[$sectionName] = [];
            }
            $tagsBySectionName[$sectionName][] = $tag;
        }

        foreach ($tagsBySectionName AS $sectionName => $tags) {

            if ($sectionName != '-') {
                $sectionId = strtolower(preg_replace('/[^a-zA-Z]/', '', $sectionName));
                $this->openSection($sectionName, $sectionId);
            }

            /** @var $tag \AppBundle\Entity\ProductTag */
            foreach ($tags AS $tag) {

                if ($tag->getShowOnWebsite() != true) {
                    continue;
                }

                $label = $tag->getName();
                $uri = $this->router->generate('public_products', [
                    'tagId' => $tag->getId(),
                    'section' => $sectionId,
                    'locationId' => $request->get('locationId'),
                ]);

                if ($request->get('tagId') == $tag->getId()) {
                    $class = 'active';
                } else {
                    $class = '';
                }

                $this->addLink($uri, $label, $class);
            }

            if ($sectionName != '-') {
                $this->closeSection();
            }

        }

        $this->menu .= '</ul>';

        return $this->menu;
    }

    private function addLink($uri, $text, $class = '', $id = '')
    {
        $this->menu .= '<li class="'.$class.'"><a href="'.$uri.'">'.$text.'</a></li>';
    }

    private function openSection($label, $id)
    {
        $request = $this->request->getCurrentRequest();
        if ($request->get('section') == $id) {
            $class = 'in';
        } else {
            $class = '';
        }
        $this->menu .= '<li class="menu-parent" id="section_'.$id.'">';
        $this->menu .= '<a data-toggle="collapse" href="#'.$id.'" data-parent="#accordion1" class="submenu-label">';
        $this->menu .= $label.'<i class="far fa-plus-square pull-right" style="margin: 3px"></i>';
        $this->menu .= '</a>';
        $this->menu .= '<ul id="'.$id.'" class="collapse menu-child '.$class.'">';
    }

    private function closeSection()
    {
        $this->menu .= '</ul></li>';
    }

}