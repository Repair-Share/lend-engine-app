<?php

namespace AppBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemCategoryListController extends Controller
{
    /**
     * @Route("admin/category/list", name="category_list")
     */
    public function listAction(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ProductTagRepository $repo */
        $repo = $em->getRepository('AppBundle:ProductTag');
        $categories = $repo->findAllOrderedByName();

        $tableHeader = array(
            'Section',
            'Category',
            'Show on website',
            'Number of items',
            ''
        );

        foreach ($categories AS $i) {

            /** @var \AppBundle\Entity\ProductTag $i */
            $countItems = $repo->countProducts($i->getId());

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => [
                    $i->getSection() ? $i->getSection()->getName() : '',
                    $i->getName(),
                    $i->getShowOnWebsite() ? 'Yes' : '',
                    $countItems[0]['cnt'],
                    ''
                ]
            );

        }

        $modalUrl = $this->generateUrl('product_tag');

        $helpText = <<<EOT
<h4 style="margin-top: 0px;">About item categories</h4>
These are used to categorise your items, both for admin and for member use.
When you set a category to be shown on the member site, it appears on the left hand menu.
Items can be assigned multiple categories. 
Change the order of your categories in the menu by dragging items using the icon on the left of each row.
EOT;

        return $this->render(
            'lists/setup_list.html.twig',
            [
                'title'      => 'Categories',
                'pageTitle'  => 'Categories',
                'entityName' => 'ProductTag',
                'addButtonText' => 'Add a category',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            ]
        );
    }

}