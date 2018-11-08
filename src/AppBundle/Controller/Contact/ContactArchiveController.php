<?php

namespace AppBundle\Controller\Contact;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ContactArchiveController extends Controller
{
    /**
     * @Route("admin/contact/{id}/archive", name="contact_archive", requirements={"id": "\d+"})
     */
    public function contactArchiveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Entity\Contact $contact */
        if (!$contact = $contactRepo->find($id)) {
            $this->addFlash("error", "We couldn't find a contact with ID {$id}.");
            return $this->redirectToRoute('contact_list');
        }

        // Validate
        if ($contact->getActiveMembership()) {
            $this->addFlash("error", "You can't archive contacts with an active membership. Cancel the membership first.");
            return $this->redirectToRoute('contact', ['id' => $contact->getId()]);
        }

        if ($loans = $contact->getLoans()) {
            foreach ($loans AS $loan) {
                /** @var \AppBundle\Entity\Loan $loan */
                if (!in_array($loan->getStatus(), [Loan::STATUS_CANCELLED, Loan::STATUS_CLOSED])) {
                    $this->addFlash("error", "This contact has open loans or reservations. Cancel or close them first.");
                    return $this->redirectToRoute('contact', ['id' => $contact->getId()]);
                }
            }
        }

        if ($request->get('anon') == 'y') {
            $contact->setFirstName("Anon");
            $contact->setLastName("Anon");
            $contact->setAddressLine1("-");
            $contact->setAddressLine2("-");
            $contact->setAddressLine3("-");
            $contact->setAddressLine4("-");
            $contact->setEmail("");

            $this->addFlash('success', "Contact personal data was removed");
        }

        $contact->setIsActive(false);
        $contact->setEnabled(false);
        $em->persist($contact);
        $em->flush();

        $this->addFlash('success', "Contact archived OK. If you need to re-activate this contact, please get in touch with Lend Engine support. Anonymised contact data cannot be retrieved.");
        return $this->redirectToRoute('contact_list');
    }

}