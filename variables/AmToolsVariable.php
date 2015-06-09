<?php
namespace Craft;

class AmToolsVariable
{
	/**
	 * Merge the given arrays and order them by date.
	 * @param array The arrays to merge.
	 * @return A new array that contains all items ordered by date.
	 */
	public function mergeAndOrderByDate($arrays)
	{
		return craft()->amTools->mergeAndOrderByDate($arrays);
	}

	public function sendAccessControlAllowOrigin($val = "*")
	{
		return craft()->amTools->sendAccessControlAllowOrigin($val);
	}

	public function getLocalImageUrl($url, $options = array())
	{
		return craft()->amTools_externalImage->getLocalImageUrl($url, $options);
	}

	public function getHeaderImages($entry = null)
	{
		return craft()->amTools->getHeaderImages($entry);
	}
}