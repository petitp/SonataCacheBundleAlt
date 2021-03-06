<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CacheCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $caches = array();

        foreach ($container->findTaggedServiceIds('sonata.cache_alt') as $id => $attributes) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $caches[$id] = new Reference($id);
        }

        if ($container->hasDefinition('sonata.cache_alt.orm.event_subscriber.default')) {
            $container->getDefinition('sonata.cache_alt.orm.event_subscriber.default')
                ->replaceArgument(1, $caches);
        }

        if ($container->hasDefinition('sonata.cache_alt.manager')) {
            $container->getDefinition('sonata.cache_alt.manager')
                ->replaceArgument(1, $caches);
        }
    }
}
