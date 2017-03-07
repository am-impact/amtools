<?php
namespace Craft;

class ResaveElementTypesCommand extends BaseCommand
{
    public function actionResaveElementTypes()
    {
        $task = craft()->tasks->createTask('AmTools_ResaveElements', Craft::t('Resave elements'));
        craft()->tasks->runTask($task);
    }
}
