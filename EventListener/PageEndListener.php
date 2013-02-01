<?php
/**
 * Created by RubikIntegration Team.
 * Date: 2/1/13
 * Time: 3:32 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace plugins\riCjLoader\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zepluf\Bundle\StoreBundle\Events;
use Zepluf\Bundle\StoreBundle\Event\CoreEvent;

class PageEndListener implements EventSubscriberInterface
{
    protected $loader;

    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    public function onPageEnd(CoreEvent $event)
    {
        $event->getResponse()->setContent($this->loader->injectAssets($event->getResponse()->getContent()));
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::onPageEnd => array('onPageEnd', -9999),
        );
    }
}
