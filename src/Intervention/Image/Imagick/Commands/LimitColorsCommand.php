<?php

namespace Intervention\Image\Imagick\Commands;

class LimitColorsCommand extends \Intervention\Image\Commands\AbstractCommand
{
    public function execute($image)
    {
        $count = $this->getArgument(0);
        $matte = $this->getArgument(1);

        // get current image size
        $size = $image->getSize();

        // build 2 color alpha mask from original alpha
        $alpha = clone $image->getCore();
        $alpha->separateImageChannel(\Imagick::CHANNEL_ALPHA);
        $alpha->paintTransparentImage('#ffffff', 0, 0);
        $alpha->separateImageChannel(\Imagick::CHANNEL_ALPHA);
        $alpha->negateImage(false);

        if ($matte) {
            
            // get matte color
            $mattecolor = $image->getDriver()->parseColor($matte)->getPixel();

            // create matte image
            $canvas = new \Imagick;
            $canvas->newImage($size->width, $size->height, $mattecolor, 'png');

            // lower colors of original and copy to matte
            $image->getCore()->quantizeImage($count, \Imagick::COLORSPACE_RGB, 0, false, false);
            $canvas->compositeImage($image->getCore(), \Imagick::COMPOSITE_DEFAULT, 0, 0);

            // copy new alpha to canvas
            $canvas->compositeImage($alpha, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);
            
            // replace core
            $image->setCore($canvas);

        } else {
            
            $image->getCore()->quantizeImage($count, \Imagick::COLORSPACE_RGB, 0, false, false);
            $image->getCore()->compositeImage($alpha, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);

        }
        
        return true;
        
    }
}