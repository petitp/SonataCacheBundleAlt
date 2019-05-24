<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('sonata_cache_alt')->children();

        $node
            ->arrayNode('cache_invalidation')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('service')->defaultValue('sonata.cache_alt.invalidation.simple')->end()
                    ->scalarNode('recorder')->defaultValue('sonata.cache_alt.recorder')->end()
                    ->arrayNode('classes')
                        ->useAttributeAsKey('id')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()

            ->arrayNode('caches')
                ->children()
                    ->arrayNode('esi')
                        ->children()
                            ->scalarNode('token')->defaultValue(hash('sha256', uniqid(mt_rand(), true)))->end()
                            ->arrayNode('servers')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('mongo')
                        ->children()
                            ->scalarNode('database')->isRequired()->end()
                            ->scalarNode('collection')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(27017)->end()
                                        ->scalarNode('host')->isRequired()->end()
                                        ->scalarNode('user')->defaultValue(null)->end()
                                        ->scalarNode('password')->defaultValue(null)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('memcached')
                        ->children()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(11211)->end()
                                        ->scalarNode('host')->end()
                                        ->scalarNode('weight')->defaultValue(0)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('memcache')
                        ->children()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(11211)->end()
                                        ->scalarNode('host')->end()
                                        ->scalarNode('weight')->defaultValue(1)->end()
                                        ->scalarNode('persistent')->defaultValue(true)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()


                //TODO AHU this should be configurable in maim memcache cache service
                     ->arrayNode('fallback_memcache')
                        ->children()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(11211)->end()
                                        ->scalarNode('host')->end()
                                        ->scalarNode('weight')->defaultValue(1)->end()
                                        ->scalarNode('persistent')->defaultValue(true)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                     ->arrayNode('fallback_memcached')
                        ->children()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(11211)->end()
                                        ->scalarNode('host')->end()
                                        ->scalarNode('weight')->defaultValue(1)->end()
                                        ->scalarNode('persistent')->defaultValue(true)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                //TODO AHU redis added
                     ->arrayNode('redis')
                        ->children()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('port')->defaultValue(6379)->end()
                                        ->scalarNode('host')->defaultValue('localhost')->end()
                                        ->scalarNode('timeout')->defaultValue(1)->end()
                                        ->scalarNode('password')->defaultValue(null)->end()
                                        ->integerNode('database')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('apc')
                        ->children()
                            ->scalarNode('token')->isRequired()->end()
                            ->scalarNode('prefix')->isRequired()->end()
                            ->arrayNode('servers')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('domain')->isRequired()->end()
                                        ->scalarNode('ip')->isRequired()->end()
                                        ->scalarNode('port')->defaultValue(80)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
