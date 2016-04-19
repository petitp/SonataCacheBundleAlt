<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Invalidation;

use Sonata\CacheAltBundle\Cache\CacheElement;
use Sonata\CacheAltBundle\Cache\CacheInterface;

interface InvalidationInterface
{
    /**
     * @param array $caches
     * @param array $array
     * @return void
     */
    function invalidate(array $caches, array $array);
}