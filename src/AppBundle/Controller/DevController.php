<?php

namespace AppBundle\Controller;

use AppBundle\Entity\InventoryItem;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;

class DevController extends Controller
{
    protected $id;

    /**
     * @Route("admin/dev", name="dev_form")
     */
    public function showAction(Request $request)
    {

        $formBuilder = $this->createFormBuilder();

        $formBuilder->add('name', 'text', array(
            'label' => 'Product name',
            'attr' => array(
                'placeholder' => 'e.g. Mr John Smith',
                'data-help' => 'Help text here'
            )
        ));

        $formBuilder->add('tags', 'choice', array(
            'multiple' => true,
            'attr' => array(
                'placeholder' => 'Start typing a tag name ...',
                'class' => 'js-tags'
            )
        ));

        $formBuilder->add('gender', 'choice', array(
            'choices'  => array(
                'm' => 'Male',
                'f' => 'Female'
            ),
            'required' => false,
            'multiple' => true
        ));

        // https://select2.github.io/examples.html
        // https://select2.github.io/options.html#ajax
        $formBuilder->add('ajax', 'choice', array(
            'required' => false,
            'multiple' => true,
            'attr' => array(
                'class' => 'ajax',
                'data-help' => 'This data is loaded in via AJAX'
            )
        ));

//        $formBuilder->add('availability', 'choice', array(
//            'choices' => array(
//                'morning'   => 'Morning',
//                'afternoon' => 'Afternoon',
//                'evening'   => 'Evening',
//            ),
//            'multiple' => false,
//            'expanded' => true
//        ));

//        $formBuilder->add('currency', 'currency', array(
//            'placeholder' => 'Choose a currency',
//        ));

//        $formBuilder->add('body', 'textarea', array(
//            'attr' => array('class' => 'tinymce'),
//            'label' => 'Text area with class TinyMCE'
//        ));

//        $formBuilder->add('price', 'money', array(
//            'divisor' => 100,
//        ));

        $formBuilder->add('daterange', 'text', array(
            'label' => 'Date range',
            'attr' => array(
                'placeholder' => 'Choose reservation date/time',
                'data-help' => 'Maximum 2 weeks',
                'class' => 'reservation'
            )
        ));

        // http://ajaxray.com/blog/symfony2-forms-bootstrap-3-datepicker-for-date-field
        $formBuilder->add('date', 'date', array(
            'widget' => 'single_text',
            'format' => 'dd-MM-yyyy',
            'attr' => array(
                'class' => 'form-control input-inline datepicker',
                'data-provide' => 'datepicker',
                'data-date-format' => 'dd-mm-yyyy'
            )
        ));

        $formBuilder->add('check', 'checkbox', array(

        ));

        // http://symfony.com/doc/current/reference/forms/types/entity.html
        $formBuilder->add('category', 'entity', array(
            'class' => 'AppBundle:Category',
            'choice_label' => 'name',
        ));

//        $formBuilder->add('foo_choices', 'choice', array(
//            'choices' => array('foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz'),
//            'preferred_choices' => array('baz'),
//        ));

//        $formBuilder->add('emails', 'collection', array(
//            'type'   => 'email',
//            'options'  => array(
//                'required'  => false,
//                'attr'      => array('class' => 'email-box'),
//                'allow_add' => true
//            ),
//        ));


        $formBuilder->add('save', 'submit', array(
            'attr' => array(
                'class' => 'btn-primary'
            )
        ));

        $form = $formBuilder->getForm();

        // Send mail
        if ( $request->get('email') ) {
            try {
                $client = new PostmarkClient($this->getParameter('postmark_api_key'));

                $message = $this->renderView(
                    'emails/new_user.html.twig',
                    array(
                        'name' => 'bob'
                    )
                );

                $toEmail = 'chris@brightpearl.com';
                $sendResult = $client->sendEmail(
                    "chris@re-use.network",
                    $toEmail,
                    "Hello from ".$this->get('service.tenant')->getAccountDomain(),
                    $message
                );

                $this->addFlash('success', 'Sent email OK');

            } catch (PostmarkException $ex) {
                $this->addFlash('error', 'Failed to send email:'.$ex->message.' : '.$ex->postmarkApiErrorCode);
            } catch (\Exception $generalException) {
                $this->addFlash('error', 'Failed to send email:'.$generalException->getMessage());
            }
        }

        return $this->render('default/dev_form.html.twig', array(
            'form' => $form->createView(),
            'title' => 'Demo page title'
        ));

    }

}