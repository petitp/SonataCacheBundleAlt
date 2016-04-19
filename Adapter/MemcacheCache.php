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

class MemcacheCache implements CacheInterface
{
    protected $servers;

    protected $prefix;

    protected $collection;

    protected $tagValueStorage;

    /**
     * @param $prefix
     * @param array $servers
     */
    public function __construct($prefix, array $servers)
    {
        $this->prefix  = $prefix;
        $this->servers = $servers;
        $this->tagValueStorage = array();
    }

//    //todo nastavenie fallback servisu cez tuto funkcionalitu treba zmenit
//    public function getPrefix()
//    {
//        return $this->prefix;
//    }
//
//    public function getServers()
//    {
//        return $this->servers;
//    }
//
//    public function setServers()
//    {
//        return $this->servers;
//    }
//    //end todo


    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->getCollection()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array(), array $tags = array())
    {
        return $this->getCollection()->delete( $this->computeCacheKeys($keys, $tags) );
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys, array $tags = array())
    {//TODO Tags
        return $this->getCollection()->get($this->computeCacheKeys($keys, $tags)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    private function getCollection()
    {
        if (!$this->collection) {
            $this->collection = new \Memcache();

            foreach ($this->servers as $server) {
                $this->collection->addServer($server['host'], $server['port'], $server['persistent'], $server['weight']);
            }
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array(), array $tags = array())
    {
        $cacheElement = new CacheElement($keys, $data, $ttl);
        $this->getCollection()->set(
            $this->computeCacheKeys($keys, $tags),
            $cacheElement,
            false,
            time() + $cacheElement->getTtl()
        );

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    private function computeCacheKeys(array $keys, array $tags = array())
    {
        ksort($keys);

        $return = $this->prefix.md5(serialize($keys)).'_'.$this->computeCacheTags($tags);
        return $return;
    }

    private function computeCacheTagName($tag)
    {
        return $this->prefix.'TAG_'.md5($tag);
    }

    private function computeCacheTags($tagNames)
    {
        //AHU support for tags in memcache
        sort($tagNames);

        $tags = array();
        foreach ($tagNames as $tagName) {
            $tagName = $this->computeCacheTagName($tagName);

            if (isset($this->tagValueStorage[$tagName])) {
                $tagValue = $this->tagValueStorage[$tagName];
            } else {
                //get current value of tag in memcache
                $tagValue = $this->getCollection()->get($tagName);
                if (!$tagValue) {
                    //POZOR! toto cislo musime pretypovat na string, abz pri serializacii daval rovnake hodnoty
                    $tagValue = (string) time();
                    $this->getCollection()->set($tagName, $tagValue, null, 3600*24);
                }
                $this->tagValueStorage[$tagName] = $tagValue;
            }

            $tags[$tagName] = $tagValue;
        }
        return md5(serialize($tags));
    }

    public function flushByTag($tagName)
    {
        //AHU: change value in tagu instead of flushing all items
        $tagName = $this->computeCacheTagName($tagName);
        $tagValue = time();
        $this->getCollection()->set($tagName, $tagValue, 0, 3600*24);
        $this->tagValueStorage[$tagName] = $tagValue;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys, array $tags = array())
    {
        //return $this->getCollection()->get($this->computeCacheKeys($keys));
        $result =  $this->getCollection()->get($this->computeCacheKeys($keys, $tags));

        if ((false !== $result) && ($result instanceof \Sonata\CacheAltBundle\Cache\CacheElement)) {
            return $result;
        }

        return false;
    }

//    public function increment(array $keys, $value, $ttl, array $tags = array())
//    {
//        $key = $this->computeCacheKeys($keys, $tags);
//        $value = (int) $value;
//        $collection = $this->getCollection();
//        $result = $collection->add(
//            $key,
//            $value,
//            false,
//            time() + $ttl
//        );
//
//        if (false === $result) {
//            $result = $collection->increment($key, $value);
//        }
//
//        return $result;
//    }
//
//    public function getNumeric($keys, array $tags = array())
//    {
//        $result =  $this->getCollection()->get($this->computeCacheKeys($keys, $tags));
//
//        if ((false !== $result)) {
//            return (int) $result;
//        }
//
//        return false;
//    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}