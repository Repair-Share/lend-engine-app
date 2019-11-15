<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
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

        if (!$loan = $loanRepo->find($id)) {
            $this->addFlash('error', 'We could not find that reservation.');
        }

        $loan->setStatus(Loan::STATUS_CANCELLED);
        $em->persist($loan);

        try {
            $em->flush();
            $msg = $this->get('translator')->trans('msg_success.reservation_cancel', [], 'member_site');
            $this->addFlash('success', $msg);
        } catch (\Exception $generalException) {
            $msg = $this->get('translator')->trans('msg_fail.reservation_cancel', [], 'member_site');
            $this->addFlash('error', $msg);
        }

        return $this->redirectToRoute('loans');
    }

}
