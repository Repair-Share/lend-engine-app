<?php

namespace AppBundle\Uploader\Naming;

use AppBundle\Services\SettingsService;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;

class ImageNamer implements NamerInterface
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
        // Client side re-sizing now always produces a JPG
        $imageName = sprintf('%s.%s', uniqid(), 'jpg');
        $directory = $this->settings->getTenant()->getDbSchema();
        return $directory.'/'.$imageName;
    }
}