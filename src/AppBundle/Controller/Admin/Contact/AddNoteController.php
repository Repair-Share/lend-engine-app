<?php

namespace AppBundle\Controller\Admin\Contact;

use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AddNoteController extends Controller
{

    /**
     * Modal content for adding notes
     * @Route("admin/note", name="add_note")
     */
    public function addNoteAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $contactId = $request->get('contactId');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');
        $contact = $contactRepo->find($contactId);
        $preventBorrowing = $contact->getPreventBorrowing();

        /** @var \AppBundle\Entity\Note $note */
        $note = new Note();

        $user = $this->getUser();
        $note->setCreatedBy($user);

        $loanId    = $request->get('loanId');
        $adminOnly = $request->get('adminOnly');
        $flag      = $request->get('flag');

        if ($request->get('note_text') != '') {

            if ($contactId) {
                $note->setContact($contact);
            }

            $noteText = $request->get('note_text');

            $preventBorrowing = (int)$request->get('preventBorrowing');

            if ($preventBorrowing) {

                if ($preventBorrowing === 1) {
                    $contact->setPreventBorrowing(true);
                    $em->persist($contact);

                    $noteText = '<em>Updated the flag to prevent borrowing.</em>' . PHP_EOL . PHP_EOL . $noteText;

                } elseif ($preventBorrowing === 2) {
                    $contact->setPreventBorrowing(false);
                    $em->persist($contact);

                    $noteText = '<em>Updated the flag to allow borrowing.</em>' . PHP_EOL . PHP_EOL . $noteText;
                }

            }

            if ($loanId) {
                /** @var \AppBundle\Entity\LoanRepository $loanRepo */
                $loanRepo = $em->getRepository('AppBundle:Loan');
                $loan = $loanRepo->find($loanId);
                $note->setLoan($loan);
            }

            if ($adminOnly) {
                $note->setAdminOnly(true);
            }

            $note->setText($noteText);

            $em->persist($note);

            $em->flush();
            $this->addFlash('success', 'Note added OK.');

            $goto = $request->get('goto');

            if ($goto == 'ajax') {
                return $this->render(
                    'partials/note.html.twig',
                    array(
                        'note' => $note
                    )
                );
            } else if ($goto == 'loan') {
                return $this->redirectToRoute('loan', array('id' => $note->getLoan()->getId()));
            } else if ($goto == 'contact') {
                return $this->redirectToRoute('contact', array('id' => $note->getContact()->getId()));
            } else {
                return $this->redirectToRoute('contact', array('id' => $note->getContact()->getId()));
            }

        }

        // Render the new-add form in the modal
        return $this->render(
            'modals/add_note.html.twig',
            array(
                'title'            => ($flag === 'preventBorrowing' ? 'Change borrowing flag' : 'Add a note'),
                'subTitle'         => '',
                'flag'             => $flag,
                'preventBorrowing' => $preventBorrowing
            )
        );

    }

}