<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\ProductTag;
use AppBundle\Form\Type\ProductTagType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Helpers\InputHelper;

class ItemTagController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @Route("admin/tag/", name="tag_add")
     */
    public function newTagAction(Request $request)
    {
        $inputHelper = new InputHelper();
        $tagName = $request->get('text');
        $tagName = strtoupper($inputHelper->prepareFormInput($tagName));
        $tag = new ProductTag();
        $em = $this->getDoctrine()->getManager();
        $tag->setName($tagName);
        $em->persist($tag);

        try {
            $em->flush();
        } catch (\Exception $generalException) {
            $this->addFlash('error', $generalException->getMessage());
        }

        $id = $tag->getId();
        return new Response(json_encode(array('id'=>$id)));
    }

    /**
     * Modal content for managing tags
     * @Route("admin/productTag/{id}", defaults={"id" = 0}, requirements={"id": "\d+"}, name="product_tag")
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

        $locales = explode(',', $this->get('settings')->getSettingValue('org_languages'));
        $formOptions = [
            'locales' => $locales,
            'action' => $this->generateUrl('product_tag', array('id' => $id))
        ];
        $form = $this->createForm(ProductTagType::class, $productTag, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($productTag);
            $em->flush();
            $this->addFlash('success', 'Category saved.');
            return $this->redirectToRoute('tags_list');

        }

        return $this->render(
            'modals/productTag.html.twig',
            array(
                'title' => 'Item category',
                'subTitle' => '',
                'form' => $form->createView()
            )
        );

    }

}