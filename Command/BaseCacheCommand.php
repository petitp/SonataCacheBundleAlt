<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class BaseCacheCommand extends ContainerAwareCommand
{
    /**
     * @return \Sonata\CacheAltBundle\Cache\CacheManagerServiceInterface
     */
    public function getManager()
    {
        return $this->getContainer()->get('sonata.cache_alt.manager');
    }
}