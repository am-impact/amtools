<?php
namespace Craft;

class AmTools_ResaveElementsOfTypeTask extends BaseTask
{
    private $_settings = array();
    private $_elements = array();
    private $_relationFields = array('Assets', 'Entries');

    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {
        return array(
            'criteria' => AttributeType::Mixed,
            'service' => AttributeType::String,
            'saveFunction' => AttributeType::String
        );
    }

    /**
     * Gets the total number of steps for this task.
     *
     * @return int
     */
    public function getTotalSteps()
    {
        craft()->config->maxPowerCaptain();
        $this->_settings = $this->getSettings();

        if (is_a($this->_settings['criteria'] , 'Craft\\ElementCriteriaModel')) {
            $criteria = $this->_settings['criteria'];
            $this->_elements = $criteria->find();
        }

        return count($this->_elements);
    }

    /**
     * Runs a task step.
     *
     * @param int $step
     * @return bool
     */
    public function runStep($step)
    {
        if (!empty($this->_elements[$step])) {

            // Preserve relations
            $fieldLayout = $this->_elements[$step]->getFieldLayout();
            if ($fieldLayout) {
                $fieldLayoutFields = $fieldLayout->getFields();
                if (!empty($fieldLayoutFields)) {
                    foreach ($fieldLayoutFields as $fieldLayoutField) {
                        $field = $fieldLayoutField->field;
                        if (in_array($field->type, $this->_relationFields)) {
                            $this->_elements[$step]->getContent()->{$field->handle} = $this->_elements[$step]->{$field->handle}->status(null)->limit(null)->ids();
                        }
                    }
                }
            }
            // End preserve relations

            if (!craft()->{$this->_settings['service']}->{$this->_settings['saveFunction']}($this->_elements[$step])) {
                AmToolsPlugin::log('Couldn\'t save ' . get_class($this->_elements[$step]) . ' with id ' . $this->_elements[$step]->id . ', title' . $this->_elements[$step]->title, LogLevel::Info, true);
            }

            return true;
        }

        return false;
    }
}
