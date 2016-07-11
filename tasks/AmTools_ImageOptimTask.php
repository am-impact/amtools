<?php
namespace Craft;
/**
 * Task to optimize assets
 */
class AmTools_ImageOptimTask extends BaseTask
{
    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'asset' => AttributeType::Mixed
        );
    }

    /**
     * Returns the default description for this task.
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Optimize image';
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        return 1;
    }

    /**
     * Runs a task step.
     *
     * @param int $step
     * @return bool
     */
    public function runStep($step)
    {
        $asset = craft()->assets->getFileById($this->getSettings()->asset);

        if (!empty($asset) && is_a($asset, 'Craft\\AssetFileModel')) {
            $path = craft()->amTools_imageOptim->getAssetPath($asset);

            if (file_exists($path)) {
                return craft()->amTools_imageOptim->optimizeImage($path);
            }
        }

        return true;
    }
}
