<?php

namespace AppBundle\Controller\Settings;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Form\Type\Settings\SettingsReservationsType;

class SettingsReservationsController extends Controller
{
    /** @var \AppBundle\Services\SettingsService */
    private $settings;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /**
     * @Route("admin/settings/reservations", name="settings_reservations")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function settingsReservationsAction(Request $request)
    {
        /** @var $itemService \AppBundle\Services\Item\ItemService */
        $itemService =  $this->get('service.item');

        $this->em = $this->getDoctrine()->getManager();

        $this->settings = $this->get('settings');

        $options = [
            'em' => $this->em,
            'settingsService' => $this->settings
        ];
        $form = $this->createForm(SettingsReservationsType::class, null, $options);

        $form->handleRequest($request);

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        if ($form->isSubmitted()) {

            foreach ($request->get('settings_reservations') AS $setup_key => $setup_data) {
                if ($this->settings->isValidSettingsKey($setup_key)) {
                    if (!$setting = $repo->findOneBy(['setupKey' => $setup_key])) {
                        $setting = new Setting();
                        $setting->setSetupKey($setup_key);
                    }
                    if ($this->validateSettingValue($setup_key, $setup_data)) {
                        $setting->setSetupValue($setup_data);
                        $this->em->persist($setting);
                    }
                }
            }

            try {
                $this->em->flush();
                $this->addFlash('success','Settings updated.');
            } catch (\PDOException $e) {
                $this->addFlash('error','Error updating settings.');
            }

            // We have to refresh the cached settings for this tenant
            $this->settings->getAllSettings(false);

            if ($this->settings->getSettingValue('postal_loans') && !$this->settings->getSettingValue('postal_shipping_item')) {
                $this->findOrCreateShippingItem();
            }

            return $this->redirectToRoute('settings_reservations');
        }

        if ($request->get('setAllItemsNonReservable')) {
            $q = "UPDATE inventory_item i SET i.is_reservable = 0";
            $stmt = $this->em->getConnection()->prepare($q);
            $stmt->execute();
            $this->addFlash('success','All items are now non-reservable by members.');
            return $this->redirectToRoute('settings_reservations');
        }

        if ($request->get('setAllItemsHidden')) {
            $q = "UPDATE inventory_item i SET i.show_on_website = 0";
            $stmt = $this->em->getConnection()->prepare($q);
            $stmt->execute();
            $this->addFlash('success','All items are now not shown online.');
            return $this->redirectToRoute('settings_reservations');
        }

        $shippingItemName = '';
        if ($shippingItemId = $this->settings->getSettingValue('postal_shipping_item')) {
            $shippingItem = $itemService->find($shippingItemId);
            $shippingItemName = $shippingItem->getName();
        }

        return $this->render('settings/settings_reservations.html.twig', array(
            'form' => $form->createView(),
            'shippingItemName' => $shippingItemName
        ));
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    private function validateSettingValue($key, $value)
    {
        switch ($key) {
            case "postal_shipping_item":
                return false;
                break;
        }
        return true;
    }

    private function findOrCreateShippingItem()
    {
        /** @var $itemService \AppBundle\Services\Item\ItemService */
        $itemService =  $this->get('service.item');

        $locale = $this->settings->getSettingValue('org_locale');
        $itemName = $this->get('translator')->trans('shipping.title', [], 'member_site', $locale);

        if ($items = $itemService->findBy(['name' => $itemName, 'isActive' => true])) {
            $item = $items[0];
        } else {
            $item = new InventoryItem();
            $item->setItemType(InventoryItem::TYPE_SERVICE);
            $item->setName($itemName);
            $item->setShowOnWebsite(false);
            $item->setIsReservable(false);
            $this->em->persist($item);
            $this->em->flush();
        }

        $shippingItemId = $item->getId();
        $this->settings->setSettingValue('postal_shipping_item', $shippingItemId);
    }

}