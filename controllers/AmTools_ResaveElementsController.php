<?php
namespace Craft;

/**
 * AmSocialPlatform - Fields controller
 */
class AmTools_ResaveElementsController extends BaseController
{
    public function actionIndex()
    {
        $elementTypes = explode(',', craft()->request->getRequiredParam('elementTypes', ''));
        $this->returnJson(craft()->tasks->createTask('AmTools_ResaveElements', Craft::t('Resave elements'), array('elementTypes' => $elementTypes)));
    }
}
