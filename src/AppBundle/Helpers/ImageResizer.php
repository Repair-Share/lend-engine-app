<?php

namespace AppBundle\Helpers;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ImageResizer {

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Resize an image
     *
     * @param string $image (The full image path with filename and extension)
     * @param string $newPath (The new path to where the image needs to be stored)
     * @param int $height (The new height to resize the image to)
     * @param int $width (The new width to resize the image to)
     * @return string (The new path to the reized image)
     */
    public function resizeImage($image, $newPath, $height=0, $width=0)
    {
        // Get current dimensions
        $ImageDetails = $this->getImageDetails($image);
        $orig_filename = $ImageDetails->name;
        $orig_h = $ImageDetails->height;
        $orig_w = $ImageDetails->width;
        $fileExtension = $ImageDetails->extension;
        $jpegQuality = 75;

//        // If it's smaller already, don't re-size [thumbnail]
//        if ($orig_h < $height && $height > 200) {
//            return $newPath;
//        }
//        if ($orig_w < $width && $width > 200) {
//            return $newPath;
//        }

        // create new image and fill with background colour
        $gd_image_dest = imagecreatetruecolor($width, $height);
        $bgcolor = imagecolorallocate($gd_image_dest, 255, 255, 255); // white
        imagefill($gd_image_dest, 0, 0, $bgcolor); // fill background colour

        // Get the original image
        $gd_image_src = null;
        switch( $fileExtension ){
            case 'png' :
                $gd_image_src = imagecreatefrompng($image);
                imagealphablending( $gd_image_dest, false );
                imagesavealpha( $gd_image_dest, true );
                break;
            case 'jpeg':
            case 'jpg':
                $gd_image_src = imagecreatefromjpeg($image);
                break;
            case 'gif':
                $gd_image_src = imagecreatefromgif($image);
                break;
            default:
                break;
        }

        // determine scale based on the longest edge
        if ($orig_h > $orig_w) {
            $scale = $height/$orig_h;
        } else {
            $scale = $width/$orig_w;
        }

        // calc new image dimensions
        $new_w =  $orig_w * $scale;
        $new_h =  $orig_h * $scale;

        // determine offset coords so that new image is centered
        $offest_x = ($width - $new_w) / 2;
        $offest_y = ($height - $new_h) / 2;

        // copy and resize original image into center of new image
        imagecopyresampled($gd_image_dest, $gd_image_src, $offest_x, $offest_y, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

        $filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');
        $newFileName = $newPath . $orig_filename . "." . $fileExtension;

        $imageString = '';
        switch( $fileExtension ){
            case 'png':
                ob_start();
                imagepng($gd_image_dest);
                $imageString = ob_get_clean();
                break;
            case 'jpeg':
            case 'jpg':
                ob_start();
                imagejpeg($gd_image_dest, null, $jpegQuality);
                $imageString = ob_get_clean();
                break;
            case 'gif':
                ob_start();
                imagegif($gd_image_dest);
                $imageString = ob_get_clean();
                break;
            default:
                break;
        }

        $filesystem->write($newFileName, $imageString);

        return $newPath;

    }

    public function rotateImage($fullImagePath, $writePath, $direction = 'right')
    {

        $ImageDetails  = $this->getImageDetails($fullImagePath);
        $orig_filename = $ImageDetails->name;
        $fileExtension = $ImageDetails->extension;

        // File and rotation
        if ($direction == 'right') {
            $degrees = 270;
        } else {
            $degrees = 90;
        }

        // Get the original image
        $gd_image_src = null;
        switch( $fileExtension ){
            case 'png' :
                $gd_image_src = imagecreatefrompng($fullImagePath);
                break;
            case 'jpeg':
            case 'jpg':
                $gd_image_src = imagecreatefromjpeg($fullImagePath);
                break;
            case 'gif':
                $gd_image_src = imagecreatefromgif($fullImagePath);
                break;
            default:
                die("File type not found");
                break;
        }

        $gd_image_dest = imagerotate($gd_image_src, $degrees, 0);

        $filesystem = $this->container->get('oneup_flysystem.product_image_fs_filesystem');

        $imageString = '';
        switch( $fileExtension ){
            case 'png':
                ob_start();
                imagepng($gd_image_dest);
                $imageString = ob_get_clean();
                break;
            case 'jpeg':
            case 'jpg':
                ob_start();
                imagejpeg($gd_image_dest, null, 100);
                $imageString = ob_get_clean();
                break;
            case 'gif':
                ob_start();
                imagegif($gd_image_dest);
                $imageString = ob_get_clean();
                break;
            default:
                break;
        }

        // Write to the S3 bucket
        $writePath = $writePath.$orig_filename.'.'.$fileExtension;

        $filesystem->update($writePath, $imageString);

        return true;

    }

    /**
     *
     * Gets image details such as the extension, sizes and filename and returns them as a standard object.
     *
     * @param $imageWithPath
     * @return \stdClass
     */
    private function getImageDetails($imageWithPath){
        $size = getimagesize($imageWithPath);

        $imgParts = explode("/",$imageWithPath);
        $lastPart = $imgParts[count($imgParts)-1];

        if(stristr("?",$lastPart)){
            $lastPart = substr($lastPart,0,stripos("?",$lastPart));
        }
        if(stristr("#",$lastPart)){
            $lastPart = substr($lastPart,0,stripos("#",$lastPart));
        }

        $dotPos     = stripos($lastPart,".");
        $name         = substr($lastPart,0,$dotPos);
        $extension     = substr($lastPart,$dotPos+1);

        $Details = new \stdClass();
        $Details->height    = $size[1];
        $Details->width        = $size[0];
        $Details->ratio        = $size[0] / $size[1];
        $Details->extension = $extension;
        $Details->name         = $name;

        return $Details;
    }

}
?>