<?php namespace CatDesign\FixTailorSlug;

use Event;
use Backend;
use System\Classes\PluginBase;
use CatDesign\FixTailorSlug\Classes\Event\EntryRecordModelHandler;


/**
 * Plugin Information File
 *
 * @author Semen Kuznetsov (dblackCat)
 * @link   https://cat-design.ru
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'catdesign.fixedtailorslug::lang.plugin.name',
            'description' => 'catdesign.fixedtailorslug::lang.plugin.description',
            'author' => 'CatDesign',
            'icon' => 'icon-wench'
        ];
    }



    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Event::subscribe(EntryRecordModelHandler::class);
    }
}
