<?php
namespace Craft;

class AmToolsCommand extends BaseCommand
{
    /**
     * @param $elementTypes comma separated string of elementTypes
     */
    public function actionResaveElementTypes($elementTypes)
    {
        $elementTypes = explode(',', $elementTypes);
        $task = craft()->tasks->createTask('AmTools_ResaveElements', Craft::t('Resave elements'), array('elementTypes' => $elementTypes));
        craft()->tasks->runTask($task);
    }
}
