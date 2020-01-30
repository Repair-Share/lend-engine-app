<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use AppBundle\Entity\Note;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class ReservationCancelController extends Controller
{

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("member/booking/{id}/cancel", requirements={"id": "\d+"}, name="reservation_cancel")
     */
    public function reservationCancel($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $loanRepo \AppBundle\Repository\LoanRepository */
        $loanRepo = $this->getDoctrine()->getRepository('AppBundle:Loan');

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $loanRepo->find($id)) {
            $this->addFlash('error', 'We could not find that reservation.');
        }

        if (!$user = $this->getUser()) {
            $this->addFlash('error', 'Please log in first.');
            return $this->redirectToRoute('loans');
        }

        if (!in_array($loan->getStatus(), [Loan::STATUS_PENDING, Loan::STATUS_RESERVED])) {
            $this->addFlash('error', 'You can only cancel pending or reserved loans');
            return $this->redirectToRoute('loans');
        }

        $loan->setStatus(Loan::STATUS_CANCELLED);
        $em->persist($loan);

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setLoan($loan);
        $note->setText("Cancelled by ".$user->getName().".");
        $em->persist($note);

        try {
            $em->flush();
            $msg = $this->get('translator')->trans('msg_success.reservation_cancel', [], 'member_site');
            $this->addFlash('success', $msg);

            $message = $this->renderView(
                'emails/template.html.twig',
                [
                    'heading' => "",
                    'message'  => "Reservation / loan {$loan->getId()} has been cancelled by ".$user->getName()."."
                ]
            );

            $subject = "Reservation / loan {$loan->getId()} has been cancelled.";
            $toEmail = $tenantService->getCompanyEmail();
            $toName = $tenantService->getCompanyName();

            // Send the email to admin
            $emailService->send($toEmail, $toName, $subject, $message, false);

        } catch (\Exception $generalException) {
            $msg = $this->get('translator')->trans('msg_fail.reservation_cancel', [], 'member_site');
            $this->addFlash('error', $msg);
        }

        return $this->redirectToRoute('loans');
    }

}
