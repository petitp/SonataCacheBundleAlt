<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundleAlt\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * PageExtension
 *
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataCacheAltExtension extends Extension
{
    /**
     * Loads the url shortener configuration.
     *
     * @param array            $configs    An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        if (class_exists('Doctrine\\ORM\\Version')) {
            $loader->load('orm.xml');
        }
        $loader->load('cache.xml');

        $this->configureInvalidation($container, $config);
        $this->configureCache($container, $config);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param $config
     * @return void
     */
    public function configureInvalidation(ContainerBuilder $container, $config)
    {
        $cacheManager = $container->getDefinition('sonata.cache_alt.manager');

        $cacheManager->replaceArgument(0, new Reference($config['cache_invalidation']['service']));

        $recorder = $container->getDefinition('sonata.cache_alt.model_identifier');
        foreach ($config['cache_invalidation']['classes'] as $class => $method) {
            $recorder->addMethodCall('addClass', array($class, $method));
        }

        $cacheManager->addMethodCall('setRecorder', array(new Reference($config['cache_invalidation']['recorder'])));
    }

    /**
     * @throws \RuntimeException
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param $config
     * @return void
     */
    public function configureCache(ContainerBuilder $container, $config)
    {
        if (isset($config['caches']['esi'])) {
            $container
                ->getDefinition('sonata.cache_alt.esi')
                ->replaceArgument(0, $config['caches']['esi']['token'])
                ->replaceArgument(1, $config['caches']['esi']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.esi');
        }

        if (isset($config['caches']['mongo'])) {
            if (!class_exists('\Mongo', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.mongo` service is configured, however the Mongo class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.mongo.php
or remove the mongo cache settings from the configuration file.
HELP
                );
            }

            $servers = array();
            foreach ($config['caches']['mongo']['servers'] as $server) {
                if ($server['user']) {
                    $servers[] = sprintf('%s:%s@%s:%s', $server['user'], $server['password'], $server['host'], $server['port']);
                } else {
                    $servers[] = sprintf('%s:%s', $server['host'], $server['port']);
                }
            }

            $container
                ->getDefinition('sonata.cache_alt.mongo')
                ->replaceArgument(0, $servers)
                ->replaceArgument(1, $config['caches']['mongo']['database'])
                ->replaceArgument(2, $config['caches']['mongo']['collection'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.mongo');
        }

        if (isset($config['caches']['memcached'])) {

            if (!class_exists('\Memcached', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.memcached` service is configured, however the Memcached class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.memcached.php
or remove the memcached cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.memcached')
                ->replaceArgument(0, $config['caches']['memcached']['prefix'])
                ->replaceArgument(1, $config['caches']['memcached']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.memcached');
        }

        if (isset($config['caches']['memcache'])) {

            if (!class_exists('\Memcache', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.memcache` service is configured, however the Memcache class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.memcache.php
or remove the memcache cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.memcache')
                ->replaceArgument(0, $config['caches']['memcache']['prefix'])
                ->replaceArgument(1, $config['caches']['memcache']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.memcache');
        }

        //AHU redis
        if (isset($config['caches']['redis'])) {

            if (!class_exists('\Redis', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.redis` service is configured, however the Redis class is not available.

To resolve this issue, please install the related library : https://github.com/nicolasff/phpredis
or remove the redis cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.redis')
                ->replaceArgument(0, $config['caches']['redis']['prefix'])
                ->replaceArgument(1, $config['caches']['redis']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.redis');
        }

        //AHU file cache
        if (isset($config['caches']['file'])) {

            $container
                ->getDefinition('sonata.cache_alt.file')
                ->replaceArgument(0, $config['caches']['file']['prefix'])
                ->replaceArgument(1, $config['caches']['file']['directories'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.file');
        }

        //TODO AHU this should be changed
        if (isset($config['caches']['fallback_memcache'])) {

            if (!class_exists('\Memcache', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.memcache` service is configured, however the Memcache class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.memcache.php
or remove the memcache cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.fallback_memcache')
                ->replaceArgument(0, $config['caches']['fallback_memcache']['prefix'])
                ->replaceArgument(1, $config['caches']['fallback_memcache']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.fallback_memcache');
        }

        if (isset($config['caches']['fallback_memcached'])) {

            if (!class_exists('\Memcached', true)) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.memcached` service is configured, however the Memcached class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.memcached.php
or remove the memcached cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.fallback_memcached')
                ->replaceArgument(0, $config['caches']['fallback_memcached']['prefix'])
                ->replaceArgument(1, $config['caches']['fallback_memcached']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.fallback_memcached');
        }
        //end TODO









        if (isset($config['caches']['apc'])) {

            if (!function_exists('apc_fetch')) {
                throw new \RuntimeException(<<<HELP
The `sonata.cache_alt.apc` service is configured, however the apc_* functions are not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.apc.php
or remove the APC cache settings from the configuration file.
HELP
                );
            }

            $container
                ->getDefinition('sonata.cache_alt.apc')
                ->replaceArgument(1, $config['caches']['apc']['token'])
                ->replaceArgument(2, $config['caches']['apc']['prefix'])
                ->replaceArgument(3, $config['caches']['apc']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache_alt.apc');
        }
    }

}
