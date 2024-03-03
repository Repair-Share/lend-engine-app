<?php

namespace AppBundle\Uploader\Naming;

use AppBundle\Services\SettingsService;
use Symfony\Component\HttpFoundation\Session\Session;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;

class SiteImageNamer implements NamerInterface
{

    private $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Rename the file to put it into the tenant's folder
     * @param FileInterface $file
     * @return string
     */
    public function name(FileInterface $file)
    {
        $directory = $this->settings->getTenant(false)->getDbSchema();
        return $directory.'/site_images/'.uniqid().'-'.$file->getClientOriginalName();
    }
}