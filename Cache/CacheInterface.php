<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Cache;

interface CacheInterface
{
    /**
     * @param array $keys
     * @return \Sonata\CacheAltBundle\Cache\CacheElement
     */
    function get(array $keys, array $tags = array());

    /**
     * @param array $keys
     * @return boolean
     */
    function has(array $keys, array $tags = array());

    /**
     * @param array $keys
     * @param $value
     * @param int $ttl
     * @param array $contextualKeys
     * @return void
     */
    function set(array $keys, $value, $ttl = 84600, array $contextualKeys = array(), array $tags = array());

    /**
     * @param array $keys
     * @return void
     */
    function flush(array $keys = array(), array $tags = array());

    /**
     * @return void
     */
    function flushAll();

    /**
     * @return void
     */
    function isContextual();
}