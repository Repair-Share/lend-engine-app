<?php

namespace AppBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenancePlanListController extends Controller
{
    /**
     * @Route("admin/maintenance-plan/list", name="maintenance_plans")
     */
    public function maintenancePlanList(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\MaintenancePlanRepository $repo */
        $repo = $em->getRepository('AppBundle:MaintenancePlan');
        $plans = $repo->findAllOrderedByName(true);

        $tableHeader = array(
            'Name',
            'Is active',
            'Number of items',
            ''
        );

        foreach ($plans AS $plan) {

            /** @var \AppBundle\Entity\MaintenancePlan $plan */
            $countItems = $repo->countProducts($plan->getId());
            $name = $plan->getName();

            $tableRows[] = array(
                'id' => $plan->getId(),
                'class' => $plan->getIsActive() ? 'item-active' : 'item-inactive',
                'data' => array(
                    $name,
                    $plan->getIsActive() == true ? 'Yes' : 'No',
                    $countItems[0]['cnt'],
                    ''
                )
            );
        }

        $modalUrl = $this->generateUrl('maintenance_plan');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About maintenance plans</h4>
<p>These are used when you have to manage regular repair, cleaning or check processes for your items.</p>
<ul>
<li>Define a plan (such as "Annual electrical test")</li>
<li>Edit items to assign them one or more plans.</li>
<li>Schedule the first maintenance activity.</li>
<li>When the maintenance is completed, the next one will be automatically created.
</ul>
<p>Files and notes can be added to maintenance plans for a full record of maintenance history.</p>
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            array(
                'title'      => 'Maintenance plans',
                'pageTitle'  => 'Maintenance plans',
                'entityName' => 'MaintenancePlan',
                'addButtonText' => 'Add a maintenance plan',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => false,
                'help' => $helpText
            )
        );
    }

}