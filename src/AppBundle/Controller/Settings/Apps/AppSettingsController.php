<?php

namespace AppBundle\Controller\Settings\Apps;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppSettingsController extends AbstractController
{

    /**
     * @Route("/admin/apps/{code}/settings", name="app_settings")
     */
    public function appSettings($code, Request $request)
    {
        /** @var \AppBundle\Services\Apps\AppService $appService */
        $appService = $this->get('service.apps');

        /** @var \AppBundle\Services\SettingsService $settingService */
        $settingService = $this->get('settings');

        if (!$user = $this->getUser()){
            $this->addFlash('error', "Please log in to access this page.");
            return $this->redirectToRoute('home');
        }

        if (!$user->hasRole("ROLE_ADMIN")){
            $this->addFlash('error', "You don't have permission to view that page.");
            return $this->redirectToRoute('home');
        }

        if (!$app = $appService->get($code)) {
            $this->addFlash('error', "{$code} not found.");
            return $this->redirectToRoute('home');
        }

        $builder = $this->createFormBuilder();

        foreach ($app['settings'] AS $key => $data) {

            $fieldType = null;

            switch ($data['type']) {
                case "text":
                    $builder->add($key, TextType::class, [
                        'label' => $data['title'],
                        'required' => true,
                        'data' => $data['data'],
                        'attr' => [
                            'data-help' => $data['help']
                        ]
                    ]);
                    break;
                case "toggle":
                    $builder->add($key, \AppBundle\Form\Type\ToggleType::class, [
                        'label' => $data['title'],
                        'required' => true,
                        'choices' => ['Yes' => '1', 'No'  => '0'],
                        'expanded' => true,
                        'multiple' => false,
                        'data' => (int)$data['data'],
                        'attr' => [
                            'data-help' => $data['help']
                        ]
                    ]);
                    break;
                case "textarea":
                    $builder->add($key, TextareaType::class, [
                        'label' => $data['title'],
                        'required' => true,
                        'data' => $data['data'],
                        'attr' => [
                            'data-help' => $data['help']
                        ]
                    ]);
                    break;
            }

        }

        $builder->add('save', SubmitType::class, [
            'label' => 'Save settings',
            'attr' => [
                'class' => 'btn-success'
            ]
        ]);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($app['settings'] AS $key => $value) {
                $submittedValue = $form->get($key)->getData();
                $appService->saveSetting($code, $key, $submittedValue);
            }

            $this->addFlash("success", "Setting saved OK");
            return $this->redirectToRoute('app_settings', ['code' => $code]);
        }

        return $this->render('settings/app_settings.html.twig', [
            'appInfo' => $app,
            'form' => $form->createView()
        ]);
    }

}