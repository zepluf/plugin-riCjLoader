<?php
namespace plugins\riCjLoader;

use Zepluf\Bundle\StoreBundle\PluginCore;
use Zepluf\Bundle\StoreBundle\Event\CoreEvent;
use Zepluf\Bundle\StoreBundle\Events;

/**
 * the plugin main class
 */
class RiCjLoader extends PluginCore{
	public function init(){
		$this->container->get('event_dispatcher')->addListener(Events::onPageEnd, array($this, 'onPageEnd'), -9999);

        global $autoLoadConfig;
        // we want to include the loader into the view for easy access, we need to do it after the template is loaded
        $autoLoadConfig[200][] = array('autoType' => 'require', 'loadFile' => __DIR__ . '/init_includes.php');
	}	
    
	public function onPageEnd(CoreEvent $event)
    {
    	$event->setContent($this->container->get('ricjLoader.loader_helper')->injectAssets($event->getContent()));
    }

    public function activate(){
        riMkDir($this->container->getParameter('web_cache_dir') . '/ricjloader/');
        return true;
    }
}