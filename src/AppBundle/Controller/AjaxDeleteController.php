<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AjaxDeleteController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("admin/ajax/delete/", name="ajax_delete")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function deleteAction(Request $request)
    {
        $msg = '';

        if ($id = $request->get('id')) {
            $entity = $request->get('entity');
            switch ($entity) {
                case 'ProductTag':
                    $msg = $this->deleteProductTag($id);
                    break;
                case 'ProductField':
                    $msg = $this->deleteProductField($id);
                    break;
                case 'ProductFieldSelectOption':
                    $msg = $this->deleteProductFieldSelectOption($id);
                    break;
                case 'ContactField':
                    $msg = $this->deleteContactField($id);
                    break;
                case 'ContactFieldSelectOption':
                    $msg = $this->deleteContactFieldSelectOption($id);
                    break;
                case 'InventoryLocation':
                    $msg = $this->deleteLocation($id);
                    break;
                case 'PaymentMethod':
                    $msg = $this->deletePaymentMethod($id);
                    break;
                case 'ItemCondition':
                    $msg = $this->deleteItemCondition($id);
                    break;
                case 'CheckInPrompt':
                    $msg = $this->deleteCheckInPrompt($id);
                    break;
                case 'CheckOutPrompt':
                    $msg = $this->deleteCheckOutPrompt($id);
                    break;
                case 'Site':
                    $msg = $this->deleteSite($id);
                    break;
                case 'Page':
                    $msg = $this->deletePage($id);
                    break;
                case 'OpeningTimeException':
                    $msg = $this->deleteOpeningTimeException($id);
                    break;
            }
        } else {
            $msg = 'No ID given';
        }
        return new Response(json_encode($msg));
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteProductTag($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\ProductTagRepository */
        $repo = $em->getRepository('AppBundle:ProductTag');

        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }

    }

    /**
     * @param $id
     * @return string
     */
    private function deletePaymentMethod($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\PaymentMethodRepository */
        $repo = $em->getRepository('AppBundle:PaymentMethod');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deletePage($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\PageRepository */
        $repo = $em->getRepository('AppBundle:Page');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteProductField($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\ProductFieldRepository */
        $repo = $em->getRepository('AppBundle:ProductField');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteProductFieldSelectOption($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\ProductFieldSelectOptionRepository */
        $repo = $em->getRepository('AppBundle:ProductFieldSelectOption');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteContactField($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\ContactFieldRepository */
        $repo = $em->getRepository('AppBundle:ContactField');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteContactFieldSelectOption($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $repo \AppBundle\Repository\ContactFieldSelectOptionRepository */
        $repo = $em->getRepository('AppBundle:ContactFieldSelectOption');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteLocation($id)
    {
        if ($id == 1) {
            return 'this the reserved "on loan" location.';
        }

        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:InventoryLocation');

        /** @var $repo \AppBundle\Repository\InventoryLocationRepository */
        if ($repo->validateDelete($id)) {
            $entity = $repo->find($id);
            $em->remove($entity);

            try {
                $em->flush();
                return 'OK';
            } catch (\Exception $generalException) {
                return $generalException->getMessage();
            }

        } else {
            return "inventory has used this location or it's set as the default check-in location for a site.";
        }
    }

    /**
     * @param Request $request
     * @return Response
     * @Route("admin/note/delete/", name="note_delete")
     */
    public function deleteNote(Request $request)
    {

        $id = $request->get('id');

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\NoteRepository $repo */
        $repo = $em->getRepository('AppBundle:Note');

        if ($repo->validateDelete($id)) {
            $note = $repo->find($id);
            $em->remove($note);
            $em->flush();
            $msg = 'OK';
        } else {
            $msg = 'Delete failed';
        }

        return new Response(json_encode($msg));

    }

    /**
     * @param $id
     * @return string
     */
    private function deleteItemCondition($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ItemConditionRepository $repo */
        $repo = $em->getRepository('AppBundle:ItemCondition');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return 'Ensure that no items are set to use this condition';
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteCheckInPrompt($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\CheckInPromptRepository $repo */
        $repo = $em->getRepository('AppBundle:CheckInPrompt');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteCheckOutPrompt($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\CheckOutPromptRepository $repo */
        $repo = $em->getRepository('AppBundle:CheckOutPrompt');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteOpeningTimeException($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\OpeningTimeExceptionRepository $repo */
        $repo = $em->getRepository('AppBundle:OpeningTimeException');
        $entity = $repo->find($id);
        $em->remove($entity);

        try {
            $em->flush();
            return 'OK';
        } catch (\Exception $generalException) {
            return $generalException->getMessage();
        }
    }

    /**
     * @param $id
     * @return string
     */
    private function deleteSite($id)
    {
        if ($id == 1) {
            return "You cannot delete the site with ID 1.";
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\SiteRepository $repo */
        $repo = $em->getRepository('AppBundle:Site');

        if ($repo->validateDelete($id)) {

            /** @var \AppBundle\Entity\Site $entity */
            $site = $repo->find($id);

            // Set this to null so we can delete all locations
            $site->setDefaultCheckInLocation(null);
            $em->flush();

            // Remove all locations before deleting the site
            foreach($site->getInventoryLocations() AS $location) {
                $em->remove($location);
            }
            $em->flush();

            // Remove the site
            $em->remove($site);

            try {
                $em->flush();
                $msg = 'OK';

                // Set a setting value so we don't have to count sites on each page to determine functionality

                /** @var $settingRepo \AppBundle\Repository\SettingRepository */
                $settingRepo =  $em->getRepository('AppBundle:Setting');

                if (!$setting = $settingRepo->findOneBy(['setupKey' => 'multi_site'])) {
                    $setting = new Setting();
                    $setting->setSetupKey('multi_site');
                }
                $sites = $this->getDoctrine()->getRepository('AppBundle:Site')->findAll();
                if (count($sites) > 1) {
                    $setting->setSetupValue(1);
                } else {
                    $setting->setSetupValue(0);
                }
                $em->persist($setting);
                $em->flush();

            } catch (\Exception $generalException) {
                return $generalException->getMessage();
            }
        } else {
            $msg = 'Transactions have been assigned to this site.';
        }

        return $msg;
    }

}