<?php

namespace AppBundle\Controller\MemberSite;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BasketCancelController
 * @package AppBundle\Controller\MemberSite
 */
class BasketCancelController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/cancel", name="basket_cancel")
     */
    public function basketCancel()
    {
        $this->get('session')->set('basket', null);
        return $this->redirectToRoute('home');
    }
}
