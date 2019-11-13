<?php

namespace AppBundle\Controller\Settings;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class TableSortController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("admin/ajax/table_sort/", name="table_sort")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function tableSortAction(Request $request)
    {

        $errors = [];
        $msg = '';

        if ($entityName = $request->get('entity')) {

            $newSequence = trim($request->get('sequence'));
            $newSequence = explode('+', $newSequence);

            $em = $this->getDoctrine()->getManager();

            switch ($entityName) {
                case 'ProductTag':
                    $repo = $em->getRepository('AppBundle:ProductTag');
                    break;
                case 'ProductSection':
                    $repo = $em->getRepository('AppBundle:ProductSection');
                    break;
                case 'ProductField':
                    $repo = $em->getRepository('AppBundle:ProductField');
                    break;
                case 'ProductFieldSelectOption':
                    $repo = $em->getRepository('AppBundle:ProductFieldSelectOption');
                    break;
                case 'ContactField':
                    $repo = $em->getRepository('AppBundle:ContactField');
                    break;
                case 'ContactFieldSelectOption':
                    $repo = $em->getRepository('AppBundle:ContactFieldSelectOption');
                    break;
                case 'LoanField':
                    $repo = $em->getRepository('AppBundle:LoanField');
                    break;
                case 'LoanFieldSelectOption':
                    $repo = $em->getRepository('AppBundle:LoanFieldSelectOption');
                    break;
                case 'ItemCondition':
                    $repo = $em->getRepository('AppBundle:ItemCondition');
                    break;
                case 'CheckInPrompt':
                    $repo = $em->getRepository('AppBundle:CheckInPrompt');
                    break;
                case 'CheckOutPrompt':
                    $repo = $em->getRepository('AppBundle:CheckOutPrompt');
                    break;
                case 'Page':
                    $repo = $em->getRepository('AppBundle:Page');
                    break;
            }

            for ($n=0; $n<count($newSequence); $n++) {
                $entityId = $newSequence[$n];
                if ($entity = $repo->find($entityId)) {
                    $entity->setSort($n);
                }
                try {
                    $em->flush();
                } catch (\Exception $generalException) {
                    $errors[] = $generalException->getMessage();
                }
            }

            if (count($errors) == 0) {
                $msg = 'OK';
            }

        } else {

            $msg = 'No entity given';

        }

        return new Response(json_encode($msg));
    }

}