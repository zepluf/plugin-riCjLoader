<?php
/**
 * Created by RubikIntegration Team.
 * Date: 12/28/12
 * Time: 12:20 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace plugins\riCjLoader\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class HandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('ricjLoader.loader_helper')) {
            $definition = $container->getDefinition('ricjLoader.loader_helper');
            foreach ($container->findTaggedServiceIds('ricjloader.handler') as $id => $attributes) {
                if (isset($attributes[0]['alias'])) {
                    $definition->addMethodCall('setHandler', array($attributes[0]['alias'], new Reference($id)));
                }
            }
        }
    }
}