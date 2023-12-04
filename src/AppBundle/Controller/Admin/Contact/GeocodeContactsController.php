<?php

namespace AppBundle\Controller\Admin\Contact;

use AppBundle\Helpers\GoogleMaps;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class GeocodeContactsController extends Controller
{

    /**
     * @Route("admin/geocode/contacts/", name="geocode_contacts")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function geocodeContactsAction(Request $request)
    {
        $geocodedOK     = 0;
        $geocodedFailed = 0;

        $this->em = $this->getDoctrine()->getManager();

        $repo = $this->em->getRepository('AppBundle:Contact');

        $contacts = $repo->findAll();

        foreach ($contacts as $contact) {

            /** @var \AppBundle\Entity\Contact $contact */
            if ((!$contact->getLatitude() || !$contact->getLongitude()) && $contact->getAddressLine1()) {

                $geo = GoogleMaps::geocodeAddressLines(
                    $contact->getAddressLine1(),
                    $contact->getAddressLine2(),
                    $contact->getAddressLine3(),
                    $contact->getAddressLine4(),
                    $contact->getCountryIsoCode()
                );

                if ($geo && $geo['lat'] && $geo['lng']) {

                    $contact->setLatitude($geo['lat']);
                    $contact->setLongitude($geo['lng']);

                    $this->em->persist($contact);
                    $this->em->flush();

                    $geocodedOK++;

                } else {

                    $geocodedFailed++;

                }

            }

        }

        if ($geocodedOK) {

            $this->addFlash(
                'success',
                $geocodedOK . ' contact' . ($geocodedOK > 1 ? 's are' : ' is') . ' geocoded'
            );

        }

        if ($geocodedFailed) {

            $this->addFlash(
                'error',
                $geocodedFailed . ' geocoding failed'
            );

        }

        return $this->redirectToRoute('contact_list');
    }

}