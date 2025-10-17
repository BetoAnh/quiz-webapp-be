<?php namespace Tober\Cors;

use System\Classes\PluginBase;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Cors',
            'description' => 'No description provided yet...',
            'author' => 'Tober',
            'icon' => 'icon-leaf'
        ];
    }
}
