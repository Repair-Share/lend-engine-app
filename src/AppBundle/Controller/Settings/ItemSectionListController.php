<?php

namespace AppBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemSectionListController extends Controller
{
    /**
     * @Route("admin/section/list", name="section_list")
     */
    public function listAction(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ProductSectionRepository $repo */
        $repo = $em->getRepository('AppBundle:ProductSection');
        $sections = $repo->findAllOrderedBySort();

        $tableHeader = array(
            'Name',
            'Show on website',
            'Number of sub categories',
            ''
        );

        /** @var \AppBundle\Entity\ProductSection $i */
        foreach ($sections AS $i) {

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => [
                    $i->getName(),
                    $i->getShowOnWebsite() ? 'Yes' : '',
                    $i->getCategories()->count(),
                    ''
                ]
            );

        }

        $modalUrl = $this->generateUrl('item_section');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About item sections (category groups)</h4>
Sections are used to group your categories into manageable sets. <br>
Change the order of your sections in the menu by dragging items using the icon on the left of each row.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            [
                'title'      => 'Item sections',
                'pageTitle'  => 'Item sections',
                'entityName' => 'ProductSection',
                'addButtonText' => 'Add a section',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            ]
        );
    }

}