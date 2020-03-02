<?php

namespace AppBundle\Controller\Loan;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a user decides to stop adding items to an existing loan
 * Class UnsetActiveLoanController
 * @package AppBundle\Controller\MemberSite
 */
class UnsetActiveLoanController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("unset-loan/{loanId}", name="unset_active_loan", requirements={"loanId": "\d+"})
     */
    public function unsetActiveLoanId($loanId)
    {
        $this->get('session')->set('active-loan', null);
        $this->get('session')->set('active-loan-type', null);
        return $this->redirectToRoute('public_loan', ['loanId' => $loanId]);
    }
}
