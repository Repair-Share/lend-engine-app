<?php

namespace AppBundle\Controller\Settings;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class PaymentMethodListController extends Controller
{

    /**
     * @Route("admin/payment-method/list", name="payment_method_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {

        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();
        $paymentMethods = $em->getRepository('AppBundle:PaymentMethod')->findAllOrderedByName(true);

        $tableHeader = array(
            'Payment method',
            'Active',
            'Actions'
        );

        foreach ($paymentMethods AS $i) {
            $tableRows[] = array(
                'id' => $i->getId(),
                'class' => $i->getIsActive() ? 'item-active' : 'item-inactive',
                'data' => array(
                    $i->getName(),
                    $i->getIsActive() ? 'Yes' : '',
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('payment_method');

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Payment methods',
                'pageTitle'  => 'Payment methods',
                'entityName' => 'PaymentMethod', // used for AJAX delete handler and modal button
                'addButtonText' => 'Add a payment method',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
            )
        );

    }

}