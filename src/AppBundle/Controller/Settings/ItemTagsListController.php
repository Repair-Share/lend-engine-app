<?php

namespace AppBundle\Controller\Settings;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ItemTagsListController extends Controller
{
    /**
     * @Route("admin/tags/list", name="tags_list")
     */
    public function listAction(Request $request)
    {
        $tableRows = array();
        $em   = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ProductTagRepository $tagRepo */
        $tagRepo = $em->getRepository('AppBundle:ProductTag');
        $tags = $tagRepo->findAllOrderedByName();

        $tableHeader = array(
            'Category',
            'Show on website',
            'Number of items',
            ''
        );

        foreach ($tags AS $i) {

            /** @var \AppBundle\Entity\ProductTag $i */
            $countItems = $tagRepo->countProducts($i->getId());
            $name = $i->getName();

            $tableRows[] = array(
                'id' => $i->getId(),
                'data' => array(
                    $name,
                    $i->getShowOnWebsite() ? 'Yes' : '',
                    $countItems[0]['cnt'],
                    ''
                )
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
            array(
                'title'      => 'Categories',
                'pageTitle'  => 'Categories',
                'entityName' => 'ProductTag',
                'addButtonText' => 'Add a category',
                'tableRows'  => $tableRows,
                'tableHeader' => $tableHeader,
                'modalUrl' => $modalUrl,
                'sortable' => true,
                'help' => $helpText
            )
        );
    }

    /**
     * @Route("admin/tags/search", name="tags_search")
     * Used to feed the Select2 AJAX tags manager
     */
    public function searchAction(Request $request)
    {
        $term = $request->get('term');
        $data = array();
        $em   = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('AppBundle:ProductTag')->searchByName($term);
        /** @var \AppBundle\Entity\ProductTag $tag */
        foreach ($tags AS $tag) {
            $data[] = array(
                'id'        => $tag->getId(),
                'text'      => $tag->getName(),
                'products'  => $tag->getInventoryItems(),
            );
        }
        return new Response(
            json_encode($data),
            200,
            array('Content-Type' => 'application/json')
        );
    }
}