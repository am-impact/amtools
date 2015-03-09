<?php
namespace Craft;

class AmToolsPlugin extends BasePlugin
{
    function getName()
    {
         return 'a&m Tools';
    }

    function getVersion()
    {
        return '1.1.1';
    }

    function getDeveloper()
    {
        return 'a&m impact';
    }

    function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    public function addTwigExtension()
    {
        Craft::import('plugins.amtools.twigextensions.ToolsTwigExtension');

        return new ToolsTwigExtension();
    }
}
