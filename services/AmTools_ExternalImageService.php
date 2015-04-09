<?php
namespace Craft;

class AmTools_ExternalImageService extends BaseApplicationComponent
{
	private $url;
	private $options;
	private $localExternalImagePath;
	private $environmentVariables;
	private $tempDir;
	private $tempImg;
	private $tempImgPath;

	/**
	 * Set the required variables and do some preparation
	 */
	public function init()
	{
		$this->environmentVariables = craft()->config->get('environmentVariables');
		$this->localExternalImagePath =  $this->environmentVariables['fileSystemPath'] . 'resources' . DIRECTORY_SEPARATOR . 'remote_images' . DIRECTORY_SEPARATOR;
		$this->tempDir =  CRAFT_STORAGE_PATH . 'amTools' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
		$this->prepareDir($this->localExternalImagePath);
		$this->prepareDir($this->tempDir);
	}

	/**
	 * Get the local url for an external image
	 * @param string $url
	 * @param array $options
	 * 
	 * @return string
	 */
	public function getLocalImageUrl($url, $options = array())
	{
		$this->url = $url;
		$this->options = $options;

		$this->normalizeOptions();
		$localImageOnDisk = $this->generateLocalFilePath();

		if ($localImageOnDisk && !file_exists($localImageOnDisk))
		{
			$this->tempImg = $this->getTempImage();
			$this->applyOptions();
			$this->tempImg->saveAs($localImageOnDisk);
			craft()->amTools_imageOptim->optimizeImage($localImageOnDisk);
			unlink($this->tempImgPath);
		}

		return $this->convertToUrl($localImageOnDisk);
	}

	/**
	 * Get a temporary image for the external image
	 *
	 * @return image
	 */
	private function getTempImage()
	{
		$this->tempImgPath = $this->tempDir . DIRECTORY_SEPARATOR . uniqid('remoteImg_');
		file_put_contents($this->tempImgPath, file_get_contents($this->url));

		$image = new Image();
		return $image->loadImage($this->tempImgPath);
	}

	/**
	 * Transform the local web accessible path to an url
	 *
	 * @return string
	 */
	private function convertToUrl($localWebAccessiblePath)
	{
		return str_replace('\\', '/', str_replace($this->environmentVariables['fileSystemPath'], $this->environmentVariables['submap'], $localWebAccessiblePath));
	}

	/**
	 * Generate a local path for the external url based on the source location and the specified options
	 *
	 * @return string
	 */
	private function generateLocalFilePath()
	{
		$generatedPath = $this->localExternalImagePath;
		$parts = parse_url($this->url);
		$generatedPath .= $parts['host'] . DIRECTORY_SEPARATOR;
		$pathParts = explode('/', $parts['path']);
		$numPathParts = count($pathParts);

		for ($i = 0; $i < $numPathParts - 1; $i++)
		{
			if (trim($pathParts[$i]) != '')
			{
				$generatedPath .= $pathParts[$i] . DIRECTORY_SEPARATOR;
			}
		}

		$generatedPath .= $this->getOptionsDir() . DIRECTORY_SEPARATOR;

		$this->prepareDir($generatedPath);
		return $generatedPath . $pathParts[$numPathParts - 1];
	}

	/**
	 * Assure that the global options variable only contains valid values
	 */
	private function normalizeOptions()
	{
		if (!isset($this->options['mode']) || !in_array($this->options['mode'], array('stretch', 'fit', 'crop')))
		{
			$this->options['mode'] = 'original';
		}
		if (!isset($this->options['height']) || !is_numeric($this->options['height']) || $this->options['height'] == 0)
		{
			$this->options['height'] = null;
		}
		if (!isset($this->options['width']) || !is_numeric($this->options['width']) || $this->options['width'] == 0)
		{
			$this->options['width'] = null;
		}
		if (!isset($this->options['position']) || !in_array($this->options['position'], array('top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right')))
		{
			$this->options['position'] = 'center-center';
		}
	}

	/**
	 * Create a directory name corresponding to the specified options
	 *
	 * @return string
	 */
	private function getOptionsDir()
	{
		return '_' . (is_numeric($this->options['width']) ? $this->options['width'] : 'AUTO') . 'x' . (is_numeric($this->options['height']) ? $this->options['height'] : 'AUTO') . '_' . $this->options['mode'] . '_' . $this->options['position'];
	}

	/**
	 * Apply the specified options on the temporary file
	 */
	private function applyOptions()
	{
		switch ($this->options['mode'])
		{
			case 'fit':
			{
				$this->tempImg->scaleToFit($this->options['width'], $this->options['height']);
				break;
			}

			case 'stretch':
			{
				$this->tempImg->resize($this->options['width'], $this->options['height']);
				break;
			}

			case 'crop':
			{
				$this->tempImg->scaleAndCrop($this->options['width'], $this->options['height'], true, $this->options['position']);
				break;
			}
		}
	}

	/**
	 * Ensure that the specified directory exists and create it if it doesn't
	 */
	private function prepareDir($dir)
	{
		return (!is_dir($dir)) ? mkdir($dir, 0777, true) : true;
	}
}