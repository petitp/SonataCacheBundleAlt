<?php


/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheAltBundle\Tests\Cache\Invalidation;

use Sonata\CacheAltBundle\Invalidation\ModelCollectionIdentifiers;
use Sonata\CacheAltBundle\Invalidation\DoctrineORMListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

class DoctrineORMListenerTest_Model
{
    public function getCacheIdentifier()
    {
        return '1';
    }
}

class DoctrineORMListenerTest extends \PHPUnit_Framework_TestCase
{
    public function test()
    {
        $collection = new ModelCollectionIdentifiers;

        $listener = new DoctrineORMListener($collection, array());

        $event = $this->getMock('Doctrine\ORM\Event\LifecycleEventArgs', array(), array(), '', false);
        $event->expects($this->exactly(4))
            ->method('getEntity')
            ->will($this->returnValue(new DoctrineORMListenerTest_Model));

        $cache = $this->getMock('Sonata\CacheAltBundle\Cache\CacheInterface');
        $cache->expects($this->exactly(2))
            ->method('flush')
            ->will($this->returnValue(true));

        $cache->expects($this->exactly(1))
            ->method('isContextual')
            ->will($this->returnValue(true));

        $listener->addCache($cache);

        $listener->preUpdate($event);
        $listener->preRemove($event);
    }
}
