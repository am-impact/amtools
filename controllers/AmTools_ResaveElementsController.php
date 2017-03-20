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
        $limit = craft()->request->getParam('limit', null);
        $offset = craft()->request->getParam('offset', 0);
        $this->returnJson(craft()->tasks->createTask('AmTools_ResaveElements', Craft::t('Resave elements'), array('elementTypes' => $elementTypes, 'limit' => $limit, 'offset' => $offset)));
    }
}
