<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddConsumablesController
 * @package AppBundle\Controller\MemberSite
 */
class AddConsumablesController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("loan/{loanId}/add-stock-item", name="add_stock_item", requirements={"loanId": "\d+"})
     */
    public function addStockItem($loanId)
    {
        $this->get('session')->set('active-loan', $loanId);
        $this->addFlash('success', "Choose stock item to add to loan {$loanId}.");
        return $this->redirectToRoute('public_products', ['stock' => 'y']);
    }
}
