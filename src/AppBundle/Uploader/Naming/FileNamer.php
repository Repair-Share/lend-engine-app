<?php

namespace AppBundle\Uploader\Naming;

use AppBundle\Services\SettingsService;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;

class FileNamer implements NamerInterface
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
        $newFileName = strtolower(trim($file->getClientOriginalName()));
        $newFileName = str_replace(' ', '-', $newFileName);
        $newFileName = preg_replace('/[^0-9a-zA-Z\-\.\d\s:]/', '', $newFileName);
        return $directory.'/files/'.uniqid().'-'.$newFileName;
    }
}