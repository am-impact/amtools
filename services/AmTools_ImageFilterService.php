<?php
namespace Craft;

class AmTools_ImageFilterService extends BaseApplicationComponent
{
    private $_asset;
    private $_url;
    private $_path;
    private $_instance;
    private $_image;

    private $_imageFolders = array();
    private $_allowedExtensions = array(
        'jpg',
        'jpeg'
    );
    private $_defaultParams = array(
        'filter' => 'fullColor',
        'quality' => 100,
        'mode' => 'crop',
        'position' => 'center-center'
    );

    /**
     * Create an image with a possible filter.
     *
     * @param AssetFileModel $asset
     * @param array          $params
     */
    public function image($asset, $params = array())
    {
        // We require an AssetFileModel
        if (! $asset instanceof AssetFileModel) {
            return false;
        }

        // Is it a proper image file?
        if (! in_array(strtolower(IOHelper::getExtension($asset->filename)), $this->_allowedExtensions)) {
            return false;
        }

        // Update filter params and validate them
        $filterParams = array_merge($this->_defaultParams, $params);
        if (! isset($filterParams['width']) || ! isset($filterParams['height'])) {
            return false;
        }
        if (! is_array($filterParams['filter']) || ! is_numeric(key($filterParams['filter']))) {
            $filterParams['filter'] = array($filterParams['filter']);
        }

        // Do we know the source folder path?
        if (! isset($this->_imageFolders[ $asset->sourceId ])) {
            $assetSource = $asset->getSource();
            $this->_imageFolders[ $asset->sourceId ] = $assetSource->settings;
        }

        $this->_asset = $asset;
        $this->_url   = $this->_imageFolders[ $asset->sourceId ]['url'];
        $this->_path  = $this->_imageFolders[ $asset->sourceId ]['path'];

        // Transform!
        return $this->_createFilterTransform($filterParams);
    }

    /**
     * Create a filter transform for an image.
     *
     * @param array $params
     *
     * @return string
     */
    private function _createFilterTransform($params)
    {
        $storeFolder = $this->_getStoreFolder($params);

        // Return existing file if already generated
        if (IOHelper::fileExists($this->_path . $storeFolder . $this->_asset->filename)) {
            return $this->_url . $storeFolder . $this->_asset->filename;
        }

        // Does the asset even exist?
        if (! IOHelper::fileExists($this->_path . $this->_asset->filename)) {
            return false;
        }

        // Create new image
        $this->_instance = new \Imagine\Imagick\Imagine();
        $this->_image = $this->_instance->open($this->_path . $this->_asset->filename);
        if (strtolower($params['mode']) == 'crop') {
            $this->_scaleAndCrop($params['width'], $params['height'], true, $params['position']);
        }
        else {
            $this->_scaleToFit($params['width'], $params['height']);
        }

        // Effect on image?
        $this->_addImageEffects($params['filter']);

        // Store the image!
        $this->_image->save($this->_path . $storeFolder . $this->_asset->filename, array('jpeg_quality' => $params['quality'], 'flatten' => true));

        // Return stored file
        return $this->_url . $storeFolder . $this->_asset->filename;
    }

    /**
     * Get the store folder path.
     *
     * @param array $params
     *
     * @return string
     */
    private function _getStoreFolder($params)
    {
        // Effect on image?
        $folderEffectName = '';
        foreach ($params['filter'] as $filter) {
            $actualFilter = $filter;
            if (is_array($filter)) {
                if (isset($filter['effect'])) {
                    $actualFilter = $filter['effect'];
                }
                elseif (isset($filter['filter'])) {
                    $actualFilter = $filter['filter'];
                }
                elseif (isset($filter['type'])) {
                    $actualFilter = $filter['type'];
                }
            }
            switch ($actualFilter) {
                case 'blur':
                    $folderEffectName .= '_blur';
                    break;

                case 'colorize':
                    if (isset($filter['color']) && substr($filter['color'], 0, 1) === '#') {
                        if (strpos($folderEffectName, '_colorize') === false) {
                            $folderEffectName .= '_colorize';
                        }
                        $folderEffectName .= '_' . substr($filter['color'], 1);
                    }
                    break;

                case 'grey':
                case 'gray':
                case 'grayscale':
                    $folderEffectName .= '_gray';
                    break;

                case 'negative':
                    $folderEffectName .= '_negative';
                    break;

                case 'sharp':
                case 'sharpen':
                    $folderEffectName .= '_sharpen';
                    break;
            }
        }

        // Store folder
        $storeFolder = '_'
                    . $params['width'] . '_'
                    . $params['height'] . '_'
                    . (strtolower($params['mode']) == 'crop' ? 'crop' : 'fit')
                    . (strtolower($params['mode']) == 'crop' ? '_' . $params['position'] : '')
                    . $folderEffectName
                    . DIRECTORY_SEPARATOR;
        IOHelper::ensureFolderExists($this->_path . $storeFolder);

        return $storeFolder;
    }

    /**
     * Add effects to the image.
     *
     * @param string $filter
     */
    private function _addImageEffects($filters)
    {
        foreach ($filters as $filter) {
            $actualFilter = $filter;
            if (is_array($filter)) {
                if (isset($filter['effect'])) {
                    $actualFilter = $filter['effect'];
                }
                elseif (isset($filter['filter'])) {
                    $actualFilter = $filter['filter'];
                }
                elseif (isset($filter['type'])) {
                    $actualFilter = $filter['type'];
                }
            }
            switch ($actualFilter) {
                case 'blur':
                    $this->_image->effects()->blur();
                    break;

                case 'colorize':
                    if (isset($filter['color']) && substr($filter['color'], 0, 1) === '#') {
                        $color = $this->_image->palette()->color($filter['color']);
                        $this->_image->effects()->colorize($color);
                    }
                    break;

                case 'grey':
                case 'gray':
                case 'grayscale':
                    $this->_image->effects()->grayscale();
                    break;

                case 'negative':
                    $this->_image->effects()->negative();
                    break;

                case 'sharp':
                case 'sharpen':
                    $this->_image->effects()->sharpen();
                    break;
            }
        }
    }

    /**
     * Crops the image to the specified coordinates.
     *
     * @param int $x1
     * @param int $x2
     * @param int $y1
     * @param int $y2
     */
    private function _crop($x1, $x2, $y1, $y2)
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        $this->_image->crop(new \Imagine\Image\Point($x1, $y1), new \Imagine\Image\Box($width, $height));
    }

    /**
     * Scale the image to fit within the specified size.
     *
     * @param int      $targetWidth
     * @param int|null $targetHeight
     * @param bool     $scaleIfSmaller
     */
    private function _scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true)
    {
        $this->_normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->_getWidth() > $targetWidth || $this->_getHeight() > $targetHeight) {
            $factor = max($this->_getWidth() / $targetWidth, $this->_getHeight() / $targetHeight);
            $this->_resize(round($this->_getWidth() / $factor), round($this->_getHeight() / $factor));
        }
    }

    /**
     * Scale and crop image to exactly fit the specified size.
     *
     * @param int      $targetWidth
     * @param int|null $targetHeight
     * @param bool     $scaleIfSmaller
     * @param string   $cropPositions
     */
    private function _scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
    {
        $this->_normalizeDimensions($targetWidth, $targetHeight);

        list($verticalPosition, $horizontalPosition) = explode("-", $cropPositions);

        if ($scaleIfSmaller || $this->_getWidth() > $targetWidth || $this->_getHeight() > $targetHeight) {
            // Scale first.
            $factor = min($this->_getWidth() / $targetWidth, $this->_getHeight() / $targetHeight);
            $newHeight = round($this->_getHeight() / $factor);
            $newWidth = round($this->_getWidth() / $factor);

            $this->_resize($newWidth, $newHeight);

            // Now crop.
            if ($newWidth - $targetWidth > 0) {
                switch ($horizontalPosition) {
                    case 'left': {
                        $x1 = 0;
                        $x2 = $x1 + $targetWidth;
                        break;
                    }
                    case 'right': {
                        $x2 = $newWidth;
                        $x1 = $newWidth - $targetWidth;
                        break;
                    }
                    default: {
                        $x1 = round(($newWidth - $targetWidth) / 2);
                        $x2 = $x1 + $targetWidth;
                        break;
                    }
                }

                $y1 = 0;
                $y2 = $y1 + $targetHeight;
            }
            elseif ($newHeight - $targetHeight > 0) {
                switch ($verticalPosition) {
                    case 'top': {
                        $y1 = 0;
                        $y2 = $y1 + $targetHeight;
                        break;
                    }
                    case 'bottom': {
                        $y2 = $newHeight;
                        $y1 = $newHeight - $targetHeight;
                        break;
                    }
                    default: {
                        $y1 = round(($newHeight - $targetHeight) / 2);
                        $y2 = $y1 + $targetHeight;
                        break;
                    }
                }

                $x1 = 0;
                $x2 = $x1 + $targetWidth;
            }
            else {
                $x1 = round(($newWidth - $targetWidth) / 2);
                $x2 = $x1 + $targetWidth;
                $y1 = round(($newHeight - $targetHeight) / 2);
                $y2 = $y1 + $targetHeight;
            }

            $this->_crop($x1, $x2, $y1, $y2);
        }
    }

    /**
     * Re-sizes the image. If $height is not specified, it will default to $width, creating a square.
     *
     * @param int      $targetWidth
     * @param int|null $targetHeight
     */
    private function _resize($targetWidth, $targetHeight = null)
    {
        $this->_normalizeDimensions($targetWidth, $targetHeight);

        $this->_image->resize(new \Imagine\Image\Box($targetWidth, $targetHeight), \Imagine\Image\ImageInterface::FILTER_LANCZOS);
    }

    /**
     * Get image width.
     *
     * @return int
     */
    private function _getWidth()
    {
        return $this->_image->getSize()->getWidth();
    }

    /**
     * Get image height.
     *
     * @return int
     */
    private function _getHeight()
    {
        return $this->_image->getSize()->getHeight();
    }

    /**
     * Normalizes the given dimensions.
     *
     * If width or height is set to 'AUTO', we calculate the missing dimension.
     *
     * @param int|string $width
     * @param int|string $height
     *
     * @throws Exception
     */
    private function _normalizeDimensions(&$width, &$height = null)
    {
        if (preg_match('/^(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)/', $width, $matches)) {
            $width  = $matches['width']  != 'AUTO' ? $matches['width']  : null;
            $height = $matches['height'] != 'AUTO' ? $matches['height'] : null;
        }

        if (! $height || ! $width) {
            list($width, $height) = ImageHelper::calculateMissingDimension($width, $height, $this->_getWidth(), $this->_getHeight());
        }
    }
}
