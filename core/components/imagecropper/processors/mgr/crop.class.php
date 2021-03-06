<?php

/**
 * ImageCropper
 *
 * Copyright 2019 by Sterc <modx@sterc.nl>
 */

class ImageCropperCropProcessor extends modProcessor
{
    /**
     * @access public.
     * @var Array.
     */
    public $languageTopics = ['imagecropper:default'];

    /**
     * @access public.
     * @return Mixed.
     */
    public function initialize()
    {
        $this->modx->getService('ImageCropper', 'ImageCropper', $this->modx->getOption('imagecropper.core_path', null, $this->modx->getOption('core_path') . 'components/imagecropper/') . 'model/imagecropper/');

        return parent::initialize();
    }

    /**
     * @access public.
     * @return Mixed.
     */
    public function process()
    {
        $base   = rtrim($this->modx->getOption('base_path', null, MODX_BASE_PATH), '/') . '/';
        $image  = '/' . ltrim($this->getProperty('image'), '/');

        if (!empty($image)) {
            if (file_exists($base . $image)) {
                $imageName      = substr($image, strrpos($image, '/') + 1);
                $imageExtension = substr($image, strrpos($image, '.') + 1);
                $imagePrefix    = substr($imageName, 0, strrpos($imageName, '.'));
                $imageHash      = $this->getProperty('cropWidth') . $this->getProperty('cropHeight') . $this->getProperty('x') . $this->getProperty('y') . $this->getProperty('scaleX') . $this->getProperty('scaleY');

                if (empty($this->modx->ImageCropper->config['crop_path'])) {
                    $imagePath  = trim(substr($image, 0, strrpos($image, '/') + 1), '/') . '/imagecropper/';
                } else {
                    $imagePath  = rtrim($this->modx->ImageCropper->config['crop_path'], '/') . '/';
                }

                $pathPlaceholders = $this->getPlaceholders();

                $imagePath = str_replace(array_keys($pathPlaceholders), array_values($pathPlaceholders), $imagePath);

                if (!is_dir($base . $imagePath)) {
                    if (!mkdir($base . $imagePath)) {
                        return $this->failure($this->modx->lexicon('imagecropper.error_image'));
                    }
                }

                if (in_array(strtolower($imageExtension), ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    $cropImage      = rtrim($imagePath, '/') . '/' . $imagePrefix . '-' . md5($imageHash) . '.' . $imageExtension;

                    $source         = imagecreatefromstring(file_get_contents($base . $image));

                    if ((int) $this->getProperty('scaleX') === -1) {
                        imageflip($source, IMG_FLIP_HORIZONTAL);
                    }

                    if ((int) $this->getProperty('scaleY') === -1) {
                        imageflip($source, IMG_FLIP_VERTICAL);
                    }

                    $cropSource     = imagecreatetruecolor((int) $this->getProperty('cropWidth'), (int) $this->getProperty('cropHeight'));
                    $canvasSource   = imagecreatetruecolor((int) $this->getProperty('canvasWidth'), (int) $this->getProperty('canvasHeight'));

                    imagealphablending($source, true);

                    imagealphablending($cropSource, false);
                    imagesavealpha($cropSource, true);
                    imagefill($cropSource, 0, 0, imagecolorallocatealpha($cropSource, 0, 0, 0, 127));

                    imagealphablending($canvasSource, false);
                    imagesavealpha($canvasSource, true);
                    imagefill($canvasSource, 0, 0, imagecolorallocatealpha($canvasSource, 0, 0, 0, 127));

                    imagecopyresampled($canvasSource, $source, - (int) $this->getProperty('x'), - (int) $this->getProperty('y'), 0, 0, (int) $this->getProperty('imageWidth'), (int) $this->getProperty('imageHeight'), (int) $this->getProperty('imageWidth'), (int) $this->getProperty('imageHeight'));
                    imagecopyresampled($cropSource, $canvasSource, 0, 0, 0, 0, (int) $this->getProperty('cropWidth'), (int) $this->getProperty('cropHeight'), (int) $this->getProperty('canvasWidth'), (int) $this->getProperty('canvasHeight'));

                    if (strtolower($imageExtension) === 'jpg') {
                        $result = imagejpeg($cropSource, $base . $cropImage, 100);
                    } else if (strtolower($imageExtension) === 'jpeg') {
                        $result = imagejpeg($cropSource, $base . $cropImage, 100);
                    } else if (strtolower($imageExtension) === 'png') {
                        $result = imagepng($cropSource, $base . $cropImage, 9);
                    } else if (strtolower($imageExtension) === 'gif') {
                        $result = imagegif($cropSource, $base . $cropImage);
                    } else {
                        $result = false;
                    }

                    imagedestroy($source);
                    imagedestroy($cropSource);
                    imagedestroy($canvasSource);

                    if ($result) {
                        return $this->success($this->modx->lexicon('imagecropper.success_image'), [
                            'image'     => $cropImage,
                            'width'     => $this->getProperty('cropWidth'),
                            'height'    => $this->getProperty('cropHeight')
                        ]);
                    }

                    return $this->failure($this->modx->lexicon('imagecropper.error_image'));
                }

                return $this->failure($this->modx->lexicon('imagecropper.error_image_not_valid'));
            }

            return $this->failure($this->modx->lexicon('imagecropper.error_image_not_exists'));
        }

        return $this->failure($this->modx->lexicon('imagecropper.error_image_not_set'));
    }

    /**
     * @access public.
     * @return Array.
     */
    public function getPlaceholders()
    {
        return [
            '[[+year]]'     => date('Y'),
            '[[+month]]'    => date('m'),
            '[[+day]]'      => date('d'),
            '[[+user]]'     => $this->modx->getUser()->get('id'),
            '[[+username]]' => $this->modx->getUser()->get('username'),
            '[[+resource]]' => $this->getProperty('resource')
        ];
    }
}

return 'ImageCropperCropProcessor';
