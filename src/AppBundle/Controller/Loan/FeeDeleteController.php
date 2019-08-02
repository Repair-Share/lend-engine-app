<?php

namespace AppBundle\Controller\Loan;

use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class LoanFeeDeleteController
 * @package AppBundle\Controller\MemberSite
 */
class FeeDeleteController extends Controller
{
    /**
     * @Route("admin/fee/{id}/delete", requirements={"id": "\d+"}, name="fee_delete")
     */
    public function deleteFeeAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Repository\PaymentRepository $repo */
        $repo = $em->getRepository('AppBundle:Payment');

        $currencySymbol = $this->get('service.tenant')->getCurrencySymbol();

        /** @var \AppBundle\Entity\Payment $payment */
        if (!$payment = $repo->find($id)) {
            $this->addFlash('error', "Fee not found");
            return $this->redirectToRoute('home');
        }

        $contact = $payment->getContact();

        // Add audit trail
        $note = new Note();
        $note->setCreatedBy($user);
        $note->setCreatedAt(new \DateTime());
        $note->setContact($contact);

        if ($loan = $payment->getLoan()) {
            $note->setLoan($loan);
        }

        $note->setText("Deleted fee of {$currencySymbol}".$payment->getAmount());

        $em->remove($payment);
        $em->persist($note);

        try {
            $em->flush();
            $contactService->recalculateBalance($contact);
            $this->addFlash('success', "Fee deleted");
        } catch (\Exception $e) {

        }

        if ($loan = $payment->getLoan()) {
            return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);
        } else {
            return $this->redirectToRoute('payments');
        }

    }
}