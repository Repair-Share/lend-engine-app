<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductTag;
use AppBundle\Form\Type\Settings\ItemCategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ItemCategoryController extends Controller
{

    /**
     * Modal content for managing tags
     * @Route("admin/category/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="product_tag")
     */
    public function productTagAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        if ($id) {
            $productTag = $this->getDoctrine()
                ->getRepository('AppBundle:ProductTag')
                ->find($id);
            if (!$productTag) {
                throw $this->createNotFoundException(
                    'No category found for id '.$id
                );
            }
        } else {
            $productTag = new ProductTag();
        }

        $formOptions = [
            'action' => $this->generateUrl('product_tag', array('id' => $id))
        ];
        $form = $this->createForm(ItemCategoryType::class, $productTag, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($productTag);
            $em->flush();
            $this->addFlash('success', 'Category saved.');
            return $this->redirectToRoute('category_list');

        }

        return $this->render(
            'modals/settings/item_category.html.twig',
            array(
                'title' => 'Item category',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }

}