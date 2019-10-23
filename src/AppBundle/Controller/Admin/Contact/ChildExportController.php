<?php

namespace AppBundle\Controller\Admin\Contact;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChildExportController extends Controller
{

    /**
     * @Route("admin/export/children/", name="export_children")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function exportChildrenAction(Request $request)
    {

        $container = $this->container;
        $response = new StreamedResponse(function() use($container) {

            $handle = fopen('php://output', 'r+');

            $header = [
                'Gender',
                'Date of Birth'
            ];

            fputcsv($handle, $header);

            $em = $this->getDoctrine()->getManager();

            /** @var \AppBundle\Entity\ChildRepository $childRepo */
            $childRepo = $em->getRepository('AppBundle:Child');
            $children = $childRepo->findAll();

            foreach ($children AS $child) {
                /** @var $child \AppBundle\Entity\Child */

                $dataArray = [
                    $child->getGender(),
                    $child->getDateOfBirth()->format("Y-m-d")
                ];

                fputcsv($handle, $dataArray);

            }

            fclose($handle);
        });

        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition','attachment; filename="children.csv"');

        return $response;

    }

}