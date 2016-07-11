<?php
namespace Craft;

class AmToolsPlugin extends BasePlugin
{
    public function getName()
    {
         return 'a&m Tools';
    }

    public function getVersion()
    {
        return '1.3.3';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    public function getSettingsHtml()
    {
        return craft()->templates->render('amtools/settings', array(
            'settings' => $this->getSettings()
        ));
    }

    public function init()
    {
        craft()->amTools_errors->initErrorHandler();
        if (craft()->request->isCpRequest())
        {
            craft()->amTools_imageOptim->registerEvents();
        }
    }

    public function addTwigExtension()
    {
        Craft::import('plugins.amtools.twigextensions.ToolsTwigExtension');

        return new ToolsTwigExtension();
    }

    protected function defineSettings()
    {
        return array(
            'useServerImageOptim' => array(AttributeType::Bool, 'default' => false),
            'useImagickImageOptim' => array(AttributeType::Bool, 'default' => false),
        );
    }
}
