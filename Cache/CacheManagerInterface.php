<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundleAlt\Cache;

use Sonata\CacheBundleAlt\Cache\CacheInterface;
use Sonata\CacheBundleAlt\Cache\CacheElement;
use Sonata\CacheBundleAlt\Invalidation\InvalidationInterface;
use Sonata\CacheBundleAlt\Invalidation\Recorder;

interface CacheManagerInterface
{
    /**
     * @param $name
     * @param \Sonata\CacheBundleAlt\Cache\CacheInterface $cacheManager
     * @return void
     */
    function addCacheService($name, CacheInterface $cacheManager);

    /**
     * @param $name
     * @return \Sonata\CacheBundleAlt\Cache\CacheInterface
     */
    function getCacheService($name);

    /**
     * Returns related cache services
     *
     * @return array
     */
    function getCacheServices();

    /**
     *
     * @param sring $id
     * @return boolean
     */
    function hasCacheService($id);

    /**
     * @param array $keys
     * @return void
     */
    function invalidate(array $keys);

    /**
     * @param \Sonata\CacheBundleAlt\Invalidation\Recorder $recorder
     * @return void
     */
    function setRecorder(Recorder $recorder);

    /**
     * @return \Sonata\CacheBundleAlt\Invalidation\Recorder
     */
    function getRecorder();
}
