<?php

namespace AppBundle\Controller\Settings;

use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use AppBundle\Entity\InventoryLocation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\ItemLocationType;

class ItemLocationController extends Controller
{

    /**
     * Handles the list and the form to add (which hides in the modal)
     * @Route("admin/location/list", name="location_list")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function listAction(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();
        $locations = $em->getRepository('AppBundle:InventoryLocation')->findOrderedByName($request->get('type'));

        $tableHeader = array(
            'Site',
            'Location name',
            'Items in this location are available to loan',
            'Active',
            '',
        );

        foreach ($locations AS $i) {

            /** @var $i \AppBundle\Entity\InventoryLocation */
            if ($i->getIsAvailable()) {
                $available = "Yes";
            } else {
                $available = "";
            }

            if ($i->getIsActive()) {
                $active = "Yes";
            } else {
                $active = "";
            }

            $tableRows[] = array(
                'id' => $i->getId(),
                'class' => $i->getIsActive() ? 'item-active' : 'item-inactive',
                'data' => array(
                    $i->getId() == 1 ? '-' : $i->getSite()->getName(),
                    $i->getName(),
                    $available,
                    $active,
                    'Delete-'.$i->getId()
                )
            );

        }

        $modalUrl = $this->generateUrl('location');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About locations</h4>
Locations should be used for physical spaces <strong>within</strong> your sites.
Examples could be shelf numbers, cupboards, containers and so on.<br>
You can set locations as 'unavailable', which can be handy for putting items aside for repair or cleaning.<br>
If an item is not on loan, it's always assigned to one of your locations.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Item locations',
                'entityName' => 'InventoryLocation', // used for AJAX delete handler
                'pageTitle'  => 'Item locations',
                'addButtonText' => 'Add an item location',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'help' => $helpText
            )
        );
    }

    /**
     * Modal content for managing locations
     * @Route("admin/location/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="location")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function locationAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $location = $this->getDoctrine()
                ->getRepository('AppBundle:InventoryLocation')
                ->find($id);
            if (!$location) {
                throw $this->createNotFoundException(
                    'No location found for id '.$id
                );
            }
        } else {
            $location = new InventoryLocation();
        }

        $form = $this->createForm(ItemLocationType::class, $location, array(
            'action' => $this->generateUrl('location', array('id' => $id))
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try{

                $em->persist($location);
                $em->flush();

                $this->addFlash('success', 'Location saved.');

                return $this->redirectToRoute('location_list');

            } catch (UniqueConstraintViolationException $e) {

                $this->addFlash('error', 'A location with the name "'.$location->getName().'" already exists.');
                return $this->redirectToRoute('location_list');

            } catch (PDOException $e) {

                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('location_list');

            }

        }

        return $this->render(
            'modals/settings/itemLocation.html.twig',
            array(
                'title' => 'Item location',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }

}