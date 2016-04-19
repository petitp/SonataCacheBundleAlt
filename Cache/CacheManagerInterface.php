<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Cache;

use Sonata\CacheAltBundle\Cache\CacheInterface;
use Sonata\CacheAltBundle\Cache\CacheElement;
use Sonata\CacheAltBundle\Invalidation\InvalidationInterface;
use Sonata\CacheAltBundle\Invalidation\Recorder;

interface CacheManagerInterface
{
    /**
     * @param $name
     * @param \Sonata\CacheAltBundle\Cache\CacheInterface $cacheManager
     * @return void
     */
    function addCacheService($name, CacheInterface $cacheManager);

    /**
     * @param $name
     * @return \Sonata\CacheAltBundle\Cache\CacheInterface
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
     * @param \Sonata\CacheAltBundle\Invalidation\Recorder $recorder
     * @return void
     */
    function setRecorder(Recorder $recorder);

    /**
     * @return \Sonata\CacheAltBundle\Invalidation\Recorder
     */
    function getRecorder();
}
