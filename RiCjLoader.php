<?php
namespace plugins\riCjLoader;

use Zepluf\Bundle\StoreBundle\PluginCore;
use Zepluf\Bundle\StoreBundle\Events;

/**
 * the plugin main class
 */
class RiCjLoader extends PluginCore
{
    public function init()
    {
        global $autoLoadConfig;
        // we want to include the loader into the view for easy access, we need to do it after the template is loaded
        $autoLoadConfig[200][] = array('autoType' => 'require', 'loadFile' => __DIR__ . '/init_includes.php');
    }

    public function activate()
    {
        riMkDir($this->container->getParameter('web.cache_dir') . '/ricjloader/');
        return true;
    }
}