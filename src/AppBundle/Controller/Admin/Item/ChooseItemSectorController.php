<?php

namespace AppBundle\Controller\Admin\Item;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ChooseItemSectorController extends Controller
{
    /**
     * @Route("admin/item_sector", name="item_sector")
     */
    public function chooseItemSectorAction()
    {
        // Check to see if user has exceeded item count
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var $repo \AppBundle\Repository\InventoryItemRepository */
        $repo = $em->getRepository('AppBundle:InventoryItem');

        /** @var $billingService \AppBundle\Services\BillingService */
        $billingService = $this->get('billing');

        $plan = $settingsService->getTenant()->getPlan();
        $maxItems = $billingService->getMaxItems($plan);

        $count = $repo->countItems();
        if ($count >= $maxItems) {
            $this->addFlash('error', "You've reached the maximum number of items allowed on your plan ($maxItems). Please archive some items or upgrade via the billing screen.");
            return $this->redirectToRoute('item_list');
        }

        return $this->render('item/item_sector.html.twig', array(

        ));
    }

}