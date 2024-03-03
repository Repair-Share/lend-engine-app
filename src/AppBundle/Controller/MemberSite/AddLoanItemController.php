<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddLoanItemController
 * @package AppBundle\Controller\MemberSite
 */
class AddLoanItemController extends Controller
{
    /**
     * Allows user to add an item to an existing loan or reservation
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("loan/{loanId}/add-loan-item", name="add_loan_item", requirements={"loanId": "\d+"})
     */
    public function addLoanItem($loanId)
    {
        /** @var \AppBundle\Entity\Loan $loan */
        $loan = $this->get('service.loan')->get($loanId);
        $this->get('session')->set('active-loan', $loanId);

        $type = '';
        switch ($loan->getStatus()) {
            case Loan::STATUS_RESERVED:
                $type = "reservation";
                break;
            case Loan::STATUS_PENDING:
                $type = "loan";
                break;
        }

        $this->get('session')->set('active-loan-type', $type);
        //$this->addFlash('success', "Choose an item to add to {$type} {$loanId}.");

        return $this->redirectToRoute('public_products');
    }
}
