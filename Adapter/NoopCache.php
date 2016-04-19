<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Adapter;

use Sonata\CacheAltBundle\Cache\CacheInterface;
use Sonata\CacheAltBundle\Cache\CacheElement;

class NoopCache implements CacheInterface
{
    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array(), array $tags = array())
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys, array $tags = array())
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array(), array $tags = array())
    {
        return new CacheElement($keys, $data, $ttl, $contextualKeys);
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys, array $tags = array())
    {
        throw new \RunTimeException('The NoopCache::get() cannot called');
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}