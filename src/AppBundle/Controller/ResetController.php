<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ResetController extends Controller
{

    /**
     * @Route("admin/reset", name="reset")
     */
    public function changeLogAction(Request $request)
    {
        if ($this->getUser()->getEmail() != 'hello@lend-engine.com') {
            $this->addFlash("error", "Reset is not allowed for this user.");
            return $this->redirectToRoute('homepage');
        }

        $em = $this->getDoctrine()->getManager();

        if ($request->get('a') == 'loans') {
            /** @var \AppBundle\Services\Loan\LoanService $service */
            $service = $this->get('service.loan');
            $repo = $em->getRepository('AppBundle:Loan');
            foreach ($repo->findAll() AS $loan) {
                if (!$service->deleteLoan($loan->getId())) {
                    foreach ($service->errors AS $error) {
                        $this->addFlash("error", $error);
                    }
                }
            }
            $this->addFlash("success", "Reset items.");
        } else if ($request->get('a') == 'items') {
            /** @var \AppBundle\Services\Item\ItemService $service */
            $service = $this->get('service.item');
            $repo = $em->getRepository('AppBundle:InventoryItem');
            foreach ($repo->findAll() AS $item) {
                if (!$service->deleteItem($item->getId())) {
                    foreach ($service->errors AS $error) {
                        $this->addFlash("error", $error);
                    }
                }
            }
            $this->addFlash("success", "Reset items.");
        }

        return $this->redirectToRoute('homepage');
    }

}
