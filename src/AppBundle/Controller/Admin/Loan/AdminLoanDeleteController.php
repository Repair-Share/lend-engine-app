<?php

namespace AppBundle\Controller\Admin\Loan;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AdminLoanDeleteController extends Controller
{
    /**
     * @Route("admin/loan/{id}/delete", name="loan_delete", defaults={"id" = 0}, requirements={"id": "\d+"})
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function loanDeleteAction($id)
    {
        /** @var \AppBundle\Services\Loan\LoanService $service */
        $service = $this->get('service.loan');
        if (!$service->deleteLoan($id)) {
            foreach ($service->errors AS $error) {
                $this->addFlash("error", $error);
            }
        }

        return $this->redirectToRoute('loan_list');

    }
}