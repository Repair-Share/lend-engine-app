<?php

namespace AppBundle\Controller\Item;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ChooseItemTypeController extends Controller
{
    /**
     * @Route("admin/item_type", name="item_type")
     */
    public function chooseItemTypeAction()
    {
        // Check to see if user has exceeded item count
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\InventoryItemRepository */
        $repo = $em->getRepository('AppBundle:InventoryItem');

        /** @var $billingService \AppBundle\Services\BillingService */
        $billingService = $this->get('billing');

        $plan = $this->get('session')->get('plan');
        $maxItems = $billingService->getMaxItems($plan);

        $count = $repo->countItems();
        if ($count >= $maxItems) {
            $this->addFlash('error', "You've reached the maximum number of items allowed on your plan ($maxItems). Please archive some items or upgrade via the billing screen.");
            return $this->redirectToRoute('item_list');
        }

        return $this->render('default/itemType.html.twig', array(

        ));
    }

}