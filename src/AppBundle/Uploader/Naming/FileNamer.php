<?php

namespace AppBundle\Uploader\Naming;

use Symfony\Component\HttpFoundation\Session\Session;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Naming\NamerInterface;

class FileNamer implements NamerInterface
{

    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Rename the file to put it into the tenant's folder
     * @param FileInterface $file
     * @return string
     */
    public function name(FileInterface $file)
    {
        $directory = $this->session->get('account_code');
        return $directory.'/files/'.uniqid().'-'.$file->getClientOriginalName();
    }
}