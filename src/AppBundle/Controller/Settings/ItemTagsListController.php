<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductTag;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\ProductTagType;

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

//        $repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
//        $defaultLanguage = $this->get('settings')->getSettingValue('org_locale');

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

//            $translations = $repository->findTranslations($i);
//            if (count($translations) > 1) {
//                foreach ($translations AS $lang => $d) {
//                    if ($lang != $defaultLanguage) {
//                        $name .= "<div style=\"font-size: 12px; color: #aaa\">{$lang} : ".$d['name'].'</div>';
//                    }
//                }
//            }

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
Items can be assigned multiple categories.<br>
<span class="label bg-orange">NEW</span> - change the order of your categories in the menu by dragging items using the icon on the left of each row.
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

    /**
     * @Route("admin/api/tags/list", name="api_tags_list")
     *
     */
    public function apiListAction()
    {
        $data = array();
        $em = $this->getDoctrine()->getManager();
        $tags = $em->getRepository('AppBundle:ProductTag')->findAllOrderedByName();
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