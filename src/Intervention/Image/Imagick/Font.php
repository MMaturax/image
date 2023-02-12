<?php

namespace Intervention\Image\Imagick;

use Intervention\Image\AbstractFont;
use Intervention\Image\Exception\RuntimeException;
use Intervention\Image\Image;

class Font extends AbstractFont
{
    /**
     * Draws font to given image at given position
     *
     * @param Image $image
     * @param int $posx
     * @param int $posy
     * @return void
     */
    public function applyToImage(Image $image, $posx = 0, $posy = 0,$returnOnlyDraw=false)
    {
        // build draw object
        $draw = new \ImagickDraw();
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);



        // set font file
        if ($this->hasApplicableFontFile()) {
            $draw->setFont($this->file);
        } else {
            throw new RuntimeException(
                "Font file must be provided to apply text to image."
            );
        }

        // parse text color
        $color = new Color($this->color);

        $draw->setFontSize($this->size);
        $draw->setFillColor($color->getPixel());
        $draw->setTextKerning($this->kerning);
        $draw->setTextInterlineSpacing($this->lineHeight);
        $draw->setFillOpacity($this->opacity / 100);

        // align horizontal
        switch (strtolower($this->align)) {
            case 'center':
                $align = \Imagick::ALIGN_CENTER;
                break;

            case 'right':
                $align = \Imagick::ALIGN_RIGHT;
                break;

            default:
                $align = \Imagick::ALIGN_LEFT;
                break;
        }

        $draw->setTextAlignment($align);

        if ($this->maxWidth){
            $multiText = $this->wordWrapAnnotation($image->getCore(),$draw,$this->text,$this->maxWidth);
            $this->text = $multiText['text'];
        }

        // align vertical
        if (strtolower($this->valign) != 'bottom') {

            // corrections on y-position
            switch (strtolower($this->valign)) {
                case 'center':
                case 'middle':
                    // calculate box size
                    $dimensions = $image->getCore()->queryFontMetrics($draw, $this->text);
                    //$posy = $posy + $dimensions['textHeight'] * 0.65 / 2;
                    $posy = $posy + $dimensions['boundingBox']['y2'] * 0.65 / 2;
                    break;

                case 'top':
                    // calculate box size
                    //$dimensions = $image->getCore()->queryFontMetrics($draw, $this->text, false);
                    $dimensions = $image->getCore()->queryFontMetrics($draw, $this->text,true); // multiline otomatik tespit etmesi için false kaldırıldı
                    //$posy = $posy + $dimensions['characterHeight'];
                    $posy = $posy + $dimensions['boundingBox']['y2'];
                    //$posy = $posy + intval(abs($dimensions['boundingBox']['y1'])) + intval(abs($dimensions['boundingBox']['y2']));


                    break;
            }
        }

        // apply to image

        $this->draw = $draw;

        if (!$returnOnlyDraw){
            $image->getCore()->annotateImage($draw, $posx, $posy, $this->angle * (-1), $this->text);
        }

        return $this->draw;

    }

    /**
     * Calculates bounding box of current font setting
     *
     * @return array
     */
    public function getBoxSize($raw = false)
    {
        $box = [];

        // build draw object
        $draw = new \ImagickDraw();
        $draw->setStrokeAntialias(true);
        $draw->setTextAntialias(true);


        // set font file
        if ($this->hasApplicableFontFile()) {
            $draw->setFont($this->file);
        } else {
            throw new RuntimeException(
                "Font file must be provided to apply text to image."
            );
        }

        $draw->setFontSize($this->size);
        $this->draw = $draw;

        if ($this->maxWidth){
            $multiText = $this->wordWrapAnnotation((new \Imagick()),$draw,$this->text,$this->maxWidth);
            $this->text = $multiText['text'];
        }

        $im= (new \Imagick());
        // $im->setImageVirtualPixelMethod(1);
        $dimensions = $im->queryFontMetrics($draw, $this->text,true);

        if (strlen($this->text) == 0) {
            // no text -> no boxsize
            $box['width'] = 0;
            $box['height'] = 0;
        } else {
            // get boxsize
            $box['width'] = intval(abs($dimensions['textWidth']));
            $box['height'] = intval(abs($dimensions['textHeight']));
        }

        if ($raw) {
            return $dimensions;
        }

        return $box;
    }



    public function wordWrapAnnotation($image, $draw, $text, $maxWidth)
    {
        $words = explode(" ", $text);
        $lines = array();
        $i = 0;
        $lineHeight = 0;
        while($i < count($words) )
        {
            $currentLine = $words[$i];
            if($i+1 >= count($words))
            {
                $lines[] = $currentLine;
                break;
            }
            //Check to see if we can add another word to this line
            $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
            while($metrics['textWidth'] <= $maxWidth)
            {
                //If so, do it and keep doing it!
                $currentLine .= ' ' . $words[++$i];
                if($i+1 >= count($words))
                    break;
                $metrics = $image->queryFontMetrics($draw, $currentLine . ' ' . $words[$i+1]);
            }
            //We can't add the next word to this line, so loop to the next line
            $lines[] = $currentLine;
            $i++;
            //Finally, update line height
            if($metrics['textHeight'] > $lineHeight)
                $lineHeight = $metrics['textHeight'];
        }

        return [
            'totalLines' =>count($lines),
            'lines'=>$lines,
            'lineHeight'=>$lineHeight,
            'text'=>implode("\n",$lines)
        ];
    }
}
