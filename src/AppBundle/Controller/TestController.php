<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TestController extends Controller {

    /**
     * @Route("clean", name="clean")
     */
    public function cleanFiles()
    {
        $filesystem = $this->container->get('oneup_flysystem.secure_file_fs_filesystem');
        $path = '/test/';
//        $filesystem->write($path."test.txt", "Hello");
//        $filesystem->createDir($path);

        if ($filesystem->deleteDir($path)) {
            $this->addFlash("success", $path);
        } else {
            $this->addFlash("error", $path);
        }

        return $this->redirectToRoute("home");
    }

}