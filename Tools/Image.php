<?php

namespace Lightning\Tools;

class Image {

    /**
     * The source image contents.
     *
     * @var resource
     *
     * TODO: Table class needs to be updated for this to be protected.
     */
    public $source;

    /**
     * The processed image data.
     *
     * @var string
     *
     * TODO: Same as above
     */
    public $processed;

    /**
     * Wrap text for image drawing.
     *
     * @param $fontSize
     * @param $angle
     * @param $fontFace
     * @param $string
     * @param $width
     *
     * @return string
     */
    public static function wrapText($fontSize, $angle, $fontFace, $string, $width){
        $ret = '';
        $arr = explode(' ', $string);
        foreach ( $arr as $word ){
            $teststring = $ret.' '.$word;
            $testbox = imagettfbbox($fontSize, $angle, $fontFace, $teststring);
            if ( $testbox[2] > $width ){
                $ret .= ($ret == '' ? '' : "\n") . $word;
            } else {
                $ret .= ($ret == '' ? '' : ' ') . $word;
            }
        }
        return $ret;
    }

    public static function loadFromPost($name) {
        $file = $_FILES[$name]['tmp_name'];
        if (!file_exists($file) || !is_uploaded_file($file)) {
            return false;
        }

        $image = new self();

        $image->source = imagecreatefromstring(file_get_contents($file));
        return $image;
    }

    public static function loadFromPostField($field) {
        $image = new self();

        $image->source = imagecreatefromstring(base64_decode(Request::post($field, 'base64')));
        return $image->source ? $image : false;
    }

    public function process($settings) {
        // Initialized some parameters.
        // The coordinates of the top left in the dest image where the src image will start.
        $dest_x = 0;
        $dest_y = 0;
        // The coordinates of the source image where the copy will start.
        $src_x = 0;
        $src_y = 0;
        // Src frame = The original image width/height
        // Dest frame = The destination image width/height
        // Dest w/h = The destination scaled image content size
        // Src w/h = The source image copy size
        $src_frame_w = $dest_frame_w = $dest_w = $src_w = imagesx($this->source);
        $src_frame_h = $dest_frame_h = $dest_h = $src_h = imagesy($this->source);

        if (!empty($settings['max_size']) && empty($settings['max_height'])) {
            $settings['max_height'] = $settings['max_size'];
        }
        if (!empty($settings['max_size']) && empty($settings['max_width'])) {
            $settings['max_width'] = $settings['max_size'];
        }

        // Set max sizes
        if (!empty($settings['max_width']) && $dest_frame_w > $settings['max_width']) {
            $dest_frame_w = $dest_w = $settings['max_width'];
            // Scale down the height.
            $dest_frame_h = $dest_h = ($dest_w * $src_h/$src_w);
        }
        if (!empty($settings['max_height']) && $dest_frame_w > $settings['max_height']) {
            $dest_frame_h = $dest_h = $settings['max_height'];
            // Scale down the width.
            $dest_frame_w = $dest_w = ($dest_h * $src_w/$src_h);
        }

        // Set absolute width/height
        if (!empty($settings['width'])) {
            $dest_frame_w = $dest_w = $settings['width'];
        }
        if (!empty($settings['height'])) {
            $dest_frame_h = $dest_h = $settings['height'];
        }

        // If the image can be cropped.
        if (!empty($settings['crop'])) {
            if (is_string($settings['crop'])) {
                switch ($settings['crop']) {
                    case 'left':
                    case 'right':
                        $settings['crop'] = ['x' => $settings['crop']];
                        break;
                    case 'x':
                        $settings['crop'] = ['x' => true];
                        break;
                    case 'bottom':
                    case 'top':
                        $settings['crop'] = ['y' => $settings['crop']];
                        break;
                    case 'y':
                        $settings['crop'] = ['y' => true];
                        break;
                }
            }
            if (!empty($settings['crop']['x']) && $settings['crop']['x'] === true) {
                $scale = $dest_frame_h / $src_frame_h;
                // Get the width of the destination image if it were scaled.
                $dest_w = $scale * $src_frame_w;
                if ($dest_w > $dest_frame_w) {
                    $dest_crop = $dest_w - $dest_frame_w;
                    $dest_w = $dest_frame_w;
                    $src_x = $dest_crop / $scale / 2;
                    $src_w = $src_frame_w - ($src_x * 2);
                } else {
                    $dest_border = $dest_frame_w - $dest_w;
                    $dest_x = $dest_border / 2;
                }
            }
            if (!empty($settings['crop']['y'])) {
                // TODO: This can be simplified.
                $scale = $src_frame_w / $src_frame_h;
                // Get the height of the destination image if it were scaled.
                $dest_h = ( int ) ($dest_frame_w / $scale);
                if ($settings['crop']['y'] == 'bottom') {
                    if ($dest_h < $dest_frame_h) {
                        $dest_border = $dest_frame_h - $dest_h;
                        $dest_y = $dest_border / 2;
                    }
                } elseif ($settings['crop']['y'] === true) {
                    if ($dest_h > $dest_frame_h) {
                        $dest_crop = $dest_h - $dest_frame_h;
                        $dest_h = $dest_frame_h;
                        $src_y = $dest_crop / $scale / 2;
                        $src_h = $src_frame_h - ($src_y * 2);
                    } else {
                        $dest_border = $dest_frame_h - $dest_h;
                        $dest_y = $dest_border / 2;
                    }
                }
            }
        }

        $this->processed = imagecreatetruecolor($dest_frame_w, $dest_frame_h);
        if (!empty($settings['alpha'])) {
            $color = imagecolorallocatealpha($this->processed, 0, 0, 0, 127);
            imagefill($this->processed, 0, 0, $color);
            imagealphablending($this->processed, false);
            imagesavealpha($this->processed, true);
        }

        imagecopyresampled(
            $this->processed, $this->source,
            $dest_x, $dest_y, $src_x, $src_y,
            $dest_w, $dest_h, $src_w, $src_h
        );
    }

    /**
     * Get the image reference to output. This will be the the processed image
     * if there is one, otherwise it will be the original image.
     *
     * @return resource
     */
    protected function &getOutputImage() {
        if (!empty($this->processed)) {
            $ref =& $this->processed;
        } else {
            $ref =& $this->source;
        }
        return $ref;
    }

    /**
     * Get the data as a PNG binary string.
     *
     * @return string
     *   binary string.
     */
    public function getPNGData() {
        ob_start();
        imagepng($this->getOutputImage());
        $contents =  ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Get the data as a JPG binary string.
     *
     * @param integer $quality
     *   The image compression quality. (0 to 100)
     *
     * @return string
     *   binary string.
     */
    public function getJPGData($quality = 80) {
        ob_start();
        imagejpeg($this->getOutputImage(), null, $quality);
        $contents =  ob_get_contents();
        ob_end_clean();
        return $contents;
    }

//    /**
//     * Write the image to a file as a PNG.
//     *
//     * @param string $file
//     *   The file name.
//     */
//    public function writePNG($file) {
//        if (strpos($file, ':') > 0) {
//            $rs = RackspaceClient::getInstance();
//            $rs->uploadFile($this->getPNGData(), $file);
//        } else {
//            $path = pathinfo($file);
//            if (!file_exists($path['dirname'])) {
//                mkdir($path['dirname'], 0777, true);
//            }
//            imagepng($this->getOutputImage(), $file);
//        }
//    }
//
//    /**
//     * Write the image to a file as a JPG.
//     *
//     * @param string $file
//     *   The file name.
//     * @param integer $quality
//     *   The image compression quality. (0 to 100)
//     */
//    public function writeJPG($file, $quality = 80) {
//        if (strpos($file, ':') > 0) {
//            $rs = RackspaceClient::getInstance();
//            $rs->uploadFile($this->getJPGData(), $file);
//        } else {
//            $path = pathinfo($file);
//            if (!file_exists($path['dirname'])) {
//                mkdir($path['dirname'], 0777, true);
//            }
//            imagejpeg($this->getOutputImage(), $file, $quality);
//        }
//    }
}