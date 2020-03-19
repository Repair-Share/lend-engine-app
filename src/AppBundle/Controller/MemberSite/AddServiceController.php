<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AddServiceController
 * @package AppBundle\Controller\MemberSite
 */
class AddServiceController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("loan/{loanId}/add-service-item", name="add_service_item", requirements={"loanId": "\d+"})
     */
    public function addServiceItem($loanId)
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
        $this->addFlash('success', "Choose a service item to add to {$type} {$loanId}.");

        return $this->redirectToRoute('public_products', ['type' => 'service']);
    }
}
