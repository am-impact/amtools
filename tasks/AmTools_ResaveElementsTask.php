<?php
namespace Craft;

class AmTools_ResaveElementsTask extends BaseTask
{
    private $_supportedElementTypes = array();
    private $_elementTypesToResave = array();
    private $_settings = array();

    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'elementTypes' => AttributeType::Mixed,
            'limit' => AttributeType::Number,
            'offset' => AttributeType::Number
        );
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        $this->_settings = $this->getSettings();

        // Define the default supported element types, the keys should be the element type class names.
        // The criteria key must contain an ElementCriteriaModel,
        // The service key must the service as in craft()->entries
        // the saveFunction key must contain the function in the service above that will be called to save the element
        $this->_supportedElementTypes = array(
            ElementType::Entry => array(
                'criteria' => craft()->elements->getCriteria(ElementType::Entry)->limit($this->_settings->limit)->offset($this->_settings->offset),
                'service' => 'entries',
                'saveFunction' => 'saveEntry',
            ),
            ElementType::User => array(
                'criteria' => craft()->elements->getCriteria(ElementType::User)->limit($this->_settings->limit)->offset($this->_settings->offset),
                'service' => 'users',
                'saveFunction' => 'saveUser'
            ),
            ElementType::Asset => array(
                'criteria' => craft()->elements->getCriteria(ElementType::Asset)->limit($this->_settings->limit)->offset($this->_settings->offset),
                'service' => 'assets',
                'saveFunction' => 'storeFile'
            ),
            ElementType::MatrixBlock => array(
                'criteria' => craft()->elements->getCriteria(ElementType::MatrixBlock)->limit($this->_settings->limit)->offset($this->_settings->offset),
                'service' => 'matrix',
                'saveFunction' => 'saveBlock'
            ),
            'AmSocialPlatform_Group' => array(
                'criteria' => craft()->elements->getCriteria('AmSocialPlatform_Group')->limit($this->_settings->limit)->offset($this->_settings->offset),
                'service' => 'amSocialPlatform_groups',
                'saveFunction' => 'saveGroup'
            )
        );

        // Allow plugins to add other custom element types
        $pluginsElementTypes = craft()->plugins->call('amToolsResaveElementTypesExtraElementTypes');
        foreach ($pluginsElementTypes as $elementTypes) {
            foreach ($elementTypes as $elementType => $elementTypeSettings) {
                $this->_supportedElementTypes[$elementType] = $elementTypeSettings;
            }
        }

        foreach ($this->_settings->elementTypes as $elementType) {
            if (!empty($this->_supportedElementTypes[$elementType])) {
                $this->_elementTypesToResave[] = $elementType;
            }
        }

        return count($this->_elementTypesToResave);
    }

    /**
     * Runs a task step.
     *
     * @param int $step
     * @return bool
     */
    public function runStep($step)
    {
        if (!empty($this->_elementTypesToResave[$step]) && !empty($this->_supportedElementTypes[$this->_elementTypesToResave[$step]])) {
            $elementType = $this->_elementTypesToResave[$step];
            $settings = $this->_supportedElementTypes[$this->_elementTypesToResave[$step]];
            return $this->runSubTask('AmTools_ResaveElementsOfType', Craft::t('Resaving elements of type ' . $elementType), $settings);
        }

        return true;
    }
}
