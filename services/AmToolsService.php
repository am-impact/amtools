<?php
namespace Craft;

class AmToolsService extends BaseApplicationComponent
{
	/**
	 * Merge the given arrays and order them by date.
	 * @param array The arrays to merge.
	 * @return A new array that contains all items ordered by date.
	 */
	public function mergeAndOrderByDate($arrays = array())
	{
		$results = array();
		foreach ($arrays as $array)
		{
			// Continue only if we have key and data available
			if (! isset($array['key']) || ! isset($array['data'])) {
				continue;
			}

			$key = $array['key'];
			foreach ($array['data'] as $item) {
				if (isset($item[$key])) {
					// Do I need to convert the DateTime?
					$resultsKey = $item[$key] instanceof DateTime ? strtotime($item[$key]->mySqlDateTime()) : $item[$key];
					$results[] = array(
						'date' => $resultsKey,
						'data' => $item
					);
				}
			}
		}

		// Sort results based on the timestamp
		usort($results, function($a, $b) {
            return $a['date'] <= $b['date'];
        });
        return $results;
	}

	public function sendAccessControlAllowOrigin($val)
	{
		header("Access-Control-Allow-Origin: " . $val);
	}
}