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
			if($this->tempImg)
			{
				$this->applyOptions();
				$this->tempImg->saveAs($localImageOnDisk);
				craft()->amTools_imageOptim->optimizeImage($localImageOnDisk);
				unlink($this->tempImgPath);
			}
			else
			{
				return false;
			}
		}

		if (!$localImageOnDisk)
		{
			return false;
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
		while(empty($this->tempImgPath) || file_exists($this->tempImgPath))
		{
			$this->tempImgPath = $this->tempDir . DIRECTORY_SEPARATOR . uniqid('remoteImg_');
		}

		$file = @file_get_contents($this->url);

		if ($file !== false)
		{
			file_put_contents($this->tempImgPath, $file);
		}
		else
		{
			return false;
		}

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
	private function generateLocalFilePath($prepareDir = true)
	{
		$generatedPath = $this->localExternalImagePath;
		$parts = parse_url($this->url);
		$generatedPath .= $parts['host'] . DIRECTORY_SEPARATOR;
		$pathParts = explode('/', $parts['path']);
		$numPathParts = count($pathParts);
		$extension = substr(strrchr($pathParts[$numPathParts - 1], "."), 1);

		if (! ImageHelper::isImageManipulatable($extension))
		{
			return false;
		}

		for ($i = 0; $i < $numPathParts - 1; $i++)
		{
			if (trim($pathParts[$i]) != '')
			{
				$generatedPath .= $pathParts[$i] . DIRECTORY_SEPARATOR;
			}
		}

		$generatedPath .= $this->getOptionsDir() . DIRECTORY_SEPARATOR;

		if ($prepareDir)
		{
			$this->prepareDir($generatedPath);
		}
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

	public function cleanup($externalUrls = array())
	{
		if (!is_array($externalUrls))
		{
			$externalUrls = array($externalUrls);
		}

		foreach ($externalUrls as $externalUrl)
		{
			$this->url = $externalUrl;
			$this->options = array();

			$this->normalizeOptions();
			$localImageOnDisk = $this->generateLocalFilePath(false);
			$parts = explode('/', $localImageOnDisk);
			$numPathParts = count($parts);
			$fileToCleanup = $parts[$numPathParts-1];
			unset($parts[$numPathParts - 1]);
			unset($parts[$numPathParts - 2]);
			$transformationsDir = implode('/', $parts);
			$cleanupNeeded = is_dir($transformationsDir);

			if ($cleanupNeeded)
			{
				$ignore = "/(^(([\.]){1,2})$|(\.(svn|git|md))|(Thumbs\.db|\.DS_STORE))$/iu";
				$removeParent = true;
				foreach (scandir($transformationsDir) as $file)
				{
					preg_match($ignore, $file, $skip);

					if (!$skip)
					{
						if (is_dir($transformationsDir . '/' . $file))
						{
							$transformationDir = $transformationsDir . '/' . $file;
							$removeParent2 = true;

							foreach (scandir($transformationDir) as $file2)
							{
								preg_match($ignore, $file2, $skip2);

								if (!$skip2)
								{
									if ($file2 == $fileToCleanup)
									{
										unlink(realpath($transformationDir . '/' . $file2));
									}
									else
									{
										$removeParent = false;
										$removeParent2 = false;
									}
								}
							}

							if ($removeParent2)
							{
								$this->_delTree(realpath($transformationDir));
							}
						}
					}
				}

				if ($removeParent)
				{
					$this->_delTree(realpath($transformationsDir));
				}
			}
		}
	}

	private function _delTree($dir)
	{
		if (strpos($dir, realpath($this->localExternalImagePath)) !== false)
		{
			$files = array_diff(scandir($dir), array('.', '..'));

			foreach ($files as $file)
			{
				(is_dir("$dir/$file")) ? $this->_delTree("$dir/$file") : unlink("$dir/$file");
			}
			return rmdir($dir);
		}

		return false;
	}
}
