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

        $builder = $this->em->getRepository('AppBundle:Contact')->createQueryBuilder('c')
            ->where('c.geocodedString is null')
            ->andWhere('c.latitude is null')
            ->andWhere('c.longitude is null')
            ->andWhere('c.addressLine1 is not null')
            ->andWhere('c.addressLine4 is not null') // Postcode
        ;

        $builder->setMaxResults(50);

        $query    = $builder->getQuery();
        $contacts = $query->getResult();

        foreach ($contacts as $contact) {

            /** @var \AppBundle\Entity\Contact $contact */
            if ((!$contact->getLatitude() || !$contact->getLongitude()) && $contact->getAddressLine1()) {

                try {

                    $geo = GoogleMaps::geocodeAddressLines(
                        $contact->getAddressLine1(),
                        $contact->getAddressLine2(),
                        $contact->getAddressLine3(),
                        $contact->getAddressLine4(),
                        $contact->getCountryIsoCode(),
                        $contact->getGeocodedString()
                    );

                    if ($geo && $geo['lat'] && $geo['lng']) {

                        $contact->setLatitude($geo['lat']);
                        $contact->setLongitude($geo['lng']);

                        $geocodedOK++;

                    } else {

                        $geocodedFailed++;

                    }

                    $contact->setGetGeocodedString($geo['lookedUpAddress']);

                    $this->em->persist($contact);
                    $this->em->flush();

                } catch (\Exception $e) {

                    $this->addFlash(
                        'error',
                        'Geocoding service error. ' . $e->getMessage()
                    );

                    break;

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