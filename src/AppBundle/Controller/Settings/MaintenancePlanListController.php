<?php

namespace AppBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenancePlanListController extends Controller
{
    /**
     * @Route("admin/settings/maintenance-plans", name="maintenance_plans")
     */
    public function maintenancePlanList(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\MaintenancePlanRepository $repo */
        $repo = $em->getRepository('AppBundle:MaintenancePlan');
        $plans = $repo->findAllOrderedByName(true);

        $tableHeader = [
            'Name',
            'Is active',
            'Number of items',
            'Type',
            'Prevent borrow',
            'Provider',
            ''
        ];

        foreach ($plans AS $plan) {

            /** @var \AppBundle\Entity\MaintenancePlan $plan */
            $countItems = $repo->countProducts($plan->getId());
            $name = $plan->getName();

            $tableRows[] = [
                'id' => $plan->getId(),
                'class' => $plan->getIsActive() ? 'item-active' : 'item-inactive',
                'data' => [
                    $name,
                    $plan->getIsActive() == true ? 'Yes' : 'No',
                    $countItems[0]['cnt'],
                    $plan->getInterval() > 0 ? "Every {$plan->getInterval()} months" : "After each loan",
                    $plan->getPreventBorrowsIfOverdue() == true ? 'Yes' : 'No',
                    $plan->getProvider() ? $plan->getProvider()->getName() : '',
                    ''
                ]
            ];
        }

        $modalUrl = $this->generateUrl('maintenance_plan');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About maintenance plans</h4>
<p>These are used when you have to manage <strong>regular repair, cleaning or check processes</strong> for your items.</p>
<ul>
<li>Define a plan (such as "Annual electrical test")</li>
<li>Edit items to assign them one or more plans.</li>
<li>Schedule the first maintenance activity.</li>
<li>When the maintenance is completed, the next one will be automatically created.
</ul>
<p>Alternatively, you can automatically <strong>create a single maintenance</strong> for an item as soon as it's checked in, perhaps for a safety check.</p>
<p>Files and notes can be added to maintenance plans for a full record of maintenance history.</p>
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            [
                'title'      => 'Maintenance plans',
                'pageTitle'  => 'Maintenance plans',
                'entityName' => 'MaintenancePlan',
                'addButtonText' => 'Add a maintenance plan',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => false,
                'help' => $helpText
            ]
        );
    }

}