<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\PaymentMethod;
use AppBundle\Form\Type\PaymentMethodType;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class PaymentMethodController extends Controller
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

    /**
     * Modal content for managing payment methods
     * @Route("admin/payment-method/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="payment_method")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function paymentMethodAction(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $paymentMethod = $this->getDoctrine()
                ->getRepository('AppBundle:PaymentMethod')
                ->find($id);
            if (!$paymentMethod) {
                throw $this->createNotFoundException(
                    'No payment method found for id '.$id
                );
            }
        } else {
            $paymentMethod = new PaymentMethod();
        }

        $form = $this->createForm(PaymentMethodType::class, $paymentMethod, array(
            'action' => $this->generateUrl('payment_method', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try{

                $em->persist($paymentMethod);
                $em->flush();
                $this->addFlash('success', 'Payment method saved.');
                return $this->redirectToRoute('payment_method_list');

            } catch (UniqueConstraintViolationException $e) {

                $this->addFlash('error', 'A payment method with that name already exists.');
                return $this->redirectToRoute('payment_method_list');

            } catch (PDOException $e) {

                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('payment_method_list');

            }

        }

        return $this->render(
            'modals/payment_method.html.twig',
            array(
                'title' => 'Payment method',
                'subTitle' => '',
                'paymentMethod' => $paymentMethod,
                'form' => $form->createView()
            )
        );

    }

}