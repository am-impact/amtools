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
}