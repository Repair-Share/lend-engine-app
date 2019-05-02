<?php

/**
 * Class used to fetch all the settings from the DB
 *
 */

namespace AppBundle\Settings;

use AppBundle\Entity\Setting;
use AppBundle\Entity\Tenant;
use AppBundle\Entity\TenantSite;
use Doctrine\ORM\EntityManager;

class Settings
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var \AppBundle\Entity\Tenant */
    private $tenant;

    public $settings = array();

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Tenant $tenant
     * @param EntityManager $em
     */
    public function setTenant(Tenant $tenant, EntityManager $em = null)
    {
        $this->tenant = $tenant;
        if ($em) {
            $this->em = $em;
        }
    }

    /**
     * @param $key
     * @return string
     */
    public function getSettingValue($key)
    {
        // If we have an account code we use the cache, else we have to go to DB each time
        // eg when running loan reminders from console for multiple tenants
        $db = $this->em->getConnection()->getDatabase();

        if ($db && $key && isset($this->settings[$db][$key])) {
            return $this->settings[$db][$key];
        } else {
            /** @var $repo \AppBundle\Repository\SettingRepository */
            $repo =  $this->em->getRepository('AppBundle:Setting');

            // Init with empty values
            $allSettings = $repo->getSettingsKeys();
            foreach ($allSettings AS $k) {
                $this->settings[$db][$k] = '';
            }

            $settings = $repo->findAll();
            foreach ($settings AS $setting) {
                /** @var $setting \AppBundle\Entity\Setting */
                if ($key == 'org_languages' && !$setting->getSetupValue()) {
                    // Languages has not yet been set, so use the default locale
                    $locale = $repo->findOneBy(['setupKey' => 'org_locale']);
                    $setting->setSetupValue($locale);
                }
                $k = $setting->getSetupKey();
                $this->settings[$db][$k] = $setting->getSetupValue();
            }

            // Set bull value for remainder of settings
            if (!isset($this->settings[$db][$key])) {
                $this->settings[$db][$key] = null;
            }

            // Set predefined values for new (as yet unset) settings
            // These will be shown in the UI, used in the app, and saved when settings are next saved
            // THIS CODE IS IN SettingRepository AND Setting service
            $newSettings = [
                'automate_email_loan_reminder' => 1,
                'automate_email_reservation_reminder' => 1,
                'automate_email_membership' => 1,
                'org_locale' => 'en'
            ];
            foreach ($newSettings AS $k => $v) {
                if ($this->settings[$db][$k] == null) {
                    $this->settings[$db][$k] = $v;
                }
            }

            return $this->settings[$db][$key];

        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function setSettingValue($key, $value)
    {
        if (!$this->isValidSettingsKey($key)) {
            return false;
        }

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        /** @var $setting \AppBundle\Entity\Setting */
        if (!$setting = $repo->findOneBy(['setupKey' => $key])) {
            $setting = new Setting();
            $setting->setSetupKey($key);
        }

        $setting->setSetupValue($value);
        $this->em->persist($setting);
        $this->em->flush();

        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function isValidSettingsKey($key)
    {
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');
        $keys = $repo->getSettingsKeys();
        if (!in_array($key, $keys)) {
            return false;
        }
        return true;
    }

    /**
     * Push a few setting values up into Core and add/update sites
     * @param $accountCode
     * @return bool
     */
    public function updateCore($accountCode)
    {
        // We also need to get the tenant so we can update the _core database for the library directory
        /** @var $tenantRepo \AppBundle\Repository\TenantRepository */
        $tenantRepo = $this->em->getRepository('AppBundle:Tenant');

        /** @var $tenantSiteRepo \AppBundle\Repository\TenantSiteRepository */
        $tenantSiteRepo = $this->em->getRepository('AppBundle:TenantSite');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $tenantRepo->findOneBy(['stub' => $accountCode]);

        /** @var \AppBundle\Entity\Site $site */
        $sites = $this->em->getRepository('AppBundle:Site')->findAll();

        foreach ($sites AS $site) {

            if ($site->getAddress() && $site->getPostCode() && $site->getCountry()) {

                // Update the site in the _core library
                $uniqueSiteId = $tenant->getId().'-'.$site->getId();
                if (!$tenantSite = $tenantSiteRepo->findOneBy(['uniqueId' => $uniqueSiteId])) {
                    $tenantSite = new TenantSite();
                    $tenantSite->setTenant($tenant);
                    $tenantSite->setUniqueId($uniqueSiteId);
                }

                // Set the tenant's default site as the first site saved, or preferably site ID 1
                if ($site->getId() == 1 || !$tenant->getSite()) {
                    $tenant->setSite($tenantSite);
                }

                if ($site->getIsListed()) {
                    $tenantSite->setStatus(TenantSite::STATUS_ACTIVE);
                } else {
                    $tenantSite->setStatus(TenantSite::STATUS_HIDDEN);
                }

                $tenantSite->setAddress($site->getAddress());
                $tenantSite->setName($site->getName());
                $tenantSite->setPostCode($site->getPostCode());
                $tenantSite->setCountry($site->getCountry());
                $this->em->persist($tenantSite);

            }

        }

        $tenant->setTimeZone($this->getSettingValue('org_timezone'));
        $tenant->setIndustry($this->getSettingValue('industry'));
        $tenant->setName($this->getSettingValue('org_name'));
        $tenant->setOrgEmail($this->getSettingValue('org_email'));

        $this->em->persist($tenant);
        $this->em->flush();

        return true;
    }

    /**
     * A Dymo XML label template
     * @return string
     */
    public function getLabelTemplate()
    {
        if (!$labelType = $this->getSettingValue('label_type')) {
            $labelType = '11355';
        }

        switch ($labelType) {
            case "11355":
                $template = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<DieCutLabel Version="8.0" Units="twips" MediaType="Default">
  <PaperOrientation>Landscape</PaperOrientation>
  <Id>MultiPurpose11355</Id>
  <PaperName>11355 Multi-Purpose</PaperName>
  <DrawCommands>
    <RoundRectangle X="0" Y="0" Width="1080" Height="2880" Rx="180" Ry="180"/>
  </DrawCommands>
  <ObjectInfo>
    <TextObject>
      <Name>ORG_NAME</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="0" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <HorizontalAlignment>Left</HorizontalAlignment>
      <VerticalAlignment>Top</VerticalAlignment>
      <TextFitMode>ShrinkToFit</TextFitMode>
      <UseFullFontHeight>True</UseFullFontHeight>
      <Verticalized>False</Verticalized>
      <StyledText>
        <Element>
          <String>ORG_NAME_HERE</String>
          <Attributes>
            <Font Family="Helvetica" Size="10" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
            <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
          </Attributes>
        </Element>
      </StyledText>
    </TextObject>
    <Bounds X="326.4" Y="91.06181" Width="2467.2" Height="460.4243"/>
  </ObjectInfo>
  <ObjectInfo>
    <BarcodeObject>
      <Name>BARCODE</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="255" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <Text>12345</Text>
      <Type>Code128Auto</Type>
      <Size>Small</Size>
      <TextPosition>Bottom</TextPosition>
      <TextFont Family="Helvetica" Size="7" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
      <CheckSumFont Family="Helvetica" Size="10" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
      <TextEmbedding>None</TextEmbedding>
      <ECLevel>0</ECLevel>
      <HorizontalAlignment>Center</HorizontalAlignment>
      <QuietZonesPadding Left="0" Right="0" Top="0" Bottom="0"/>
    </BarcodeObject>
    <Bounds X="326.4" Y="398.4" Width="1189.002" Height="600"/>
  </ObjectInfo>
  <ObjectInfo>
    <TextObject>
      <Name>SKU</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="0" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <HorizontalAlignment>Left</HorizontalAlignment>
      <VerticalAlignment>Middle</VerticalAlignment>
      <TextFitMode>ShrinkToFit</TextFitMode>
      <UseFullFontHeight>True</UseFullFontHeight>
      <Verticalized>False</Verticalized>
      <StyledText>
        <Element>
          <String>ITEM_CODE</String>
          <Attributes>
            <Font Family="Helvetica" Size="13" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
            <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
          </Attributes>
        </Element>
      </StyledText>
    </TextObject>
    <Bounds X="1567.411" Y="340.2" Width="1160.157" Height="460.4243"/>
  </ObjectInfo>
</DieCutLabel>
EOT;
                break;

            case "1933085":
                $template = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<DieCutLabel Version="8.0" Units="twips" MediaType="Default">
  <PaperOrientation>Landscape</PaperOrientation>
  <Id>DYMOFile</Id>
  <PaperName>30345 3/4 in x 2-1/2 in</PaperName>
  <DrawCommands>
    <RoundRectangle X="0" Y="0" Width="1080" Height="3600" Rx="180" Ry="180"/>
  </DrawCommands>
  <ObjectInfo>
    <BarcodeObject>
      <Name>BARCODE</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="255" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <Text>12345</Text>
      <Type>Code128Auto</Type>
      <Size>Small</Size>
      <TextPosition>Bottom</TextPosition>
      <TextFont Family="Helvetica" Size="8" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
      <CheckSumFont Family="Helvetica" Size="10" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
      <TextEmbedding>None</TextEmbedding>
      <ECLevel>0</ECLevel>
      <HorizontalAlignment>Center</HorizontalAlignment>
      <QuietZonesPadding Left="0" Right="0" Top="0" Bottom="0"/>
    </BarcodeObject>
    <Bounds X="326.4001" Y="452.1844" Width="1236.415" Height="546.2156"/>
  </ObjectInfo>
  <ObjectInfo>
    <TextObject>
      <Name>ORG_NAME</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="0" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <HorizontalAlignment>Left</HorizontalAlignment>
      <VerticalAlignment>Top</VerticalAlignment>
      <TextFitMode>ShrinkToFit</TextFitMode>
      <UseFullFontHeight>True</UseFullFontHeight>
      <Verticalized>False</Verticalized>
      <StyledText>
        <Element>
          <String>ORG_NAME_HERE</String>
          <Attributes>
            <Font Family="Helvetica" Size="10" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
            <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
          </Attributes>
        </Element>
      </StyledText>
    </TextObject>
    <Bounds X="326.4001" Y="131.6845" Width="3187.2" Height="460.4243"/>
  </ObjectInfo>
  <ObjectInfo>
    <TextObject>
      <Name>SKU</Name>
      <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
      <BackColor Alpha="0" Red="255" Green="255" Blue="255"/>
      <LinkedObjectName></LinkedObjectName>
      <Rotation>Rotation0</Rotation>
      <IsMirrored>False</IsMirrored>
      <IsVariable>False</IsVariable>
      <HorizontalAlignment>Left</HorizontalAlignment>
      <VerticalAlignment>Middle</VerticalAlignment>
      <TextFitMode>ShrinkToFit</TextFitMode>
      <UseFullFontHeight>True</UseFullFontHeight>
      <Verticalized>False</Verticalized>
      <StyledText>
        <Element>
          <String>ITEM_CODE</String>
          <Attributes>
            <Font Family="Helvetica" Size="11" Bold="False" Italic="False" Underline="False" Strikeout="False"/>
            <ForeColor Alpha="255" Red="0" Green="0" Blue="0"/>
          </Attributes>
        </Element>
      </StyledText>
    </TextObject>
    <Bounds X="1654.125" Y="451.5325" Width="1817.67" Height="460.4243"/>
  </ObjectInfo>
</DieCutLabel>
EOT;

                break;
        }
        return $template;
    }
}