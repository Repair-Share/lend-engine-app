<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PageController
 * @package AppBundle\Controller
 */
class ThemePreviewController extends Controller
{

    /**
     * @Route("theme_preview", name="theme_preview")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function themePreviewController(Request $request)
    {
        if ($themeName = $request->get('themeName')) {
            $this->container->get('session')->set('previewThemeName', $themeName);
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("apply_theme", name="apply_theme")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function applyThemeController()
    {
        /** @var \AppBundle\Services\SettingsService $settings */
        $settings = $this->container->get('settings');
        if ($themeName = $this->container->get('session')->get('previewThemeName')) {
            $settings->setSettingValue('site_theme_name', $themeName);
            $this->container->get('session')->set('previewThemeName', null);
            $this->addFlash("success", "Theme set to {$themeName} OK.");
        } else {
            $this->addFlash("error", "There was a problem setting your theme.");
        }
        return $this->redirectToRoute('home');
    }

}
