<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundleAlt\Adapter;

use Sonata\CacheBundleAlt\Cache\CacheInterface;
use Sonata\CacheBundleAlt\Cache\CacheElement;

class MemcachedCache implements CacheInterface
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
            $this->collection = new \Memcached(); //pernament connection?

//@deprecated: method addServers should be faster
//            foreach ($this->servers as $server) {
//                $this->collection->addServer($server['host'], $server['port'], $server['weight']);
//            }

            //Important to not call ->addServers() every run -- only call it if no servers exist.
            //Otherwise, since addServers() does not check for dups, it will let you add the same server again and again and again,
            //resultings in hundreds if not thousands of connections to the MC daemon.
            $existedServers = $this->collection->getServerList();
            if (!empty($existedServers)) {
                return $this->collection;
            }

            $servers = array();

            //hash list for duplicate check
            $serversHashList = array();

            foreach ($this->servers as $server) {
                $serverHash = $server['host'].'_'.$server['port'].'_'.$server['weight'];

                if (!isset($serversHashList[$serverHash])) {

                    //prevent fromadding same server ultiple times
                    $servers[] = array(
                        $server['host'], $server['port'], $server['weight']
                    );
                    $serversHashList[$serverHash] = true;
                }
            }

            $this->collection->addServers($servers);
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
        //AHU support for tags in memcached
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
                    $this->getCollection()->set($tagName, $tagValue, 3600*24);
                }
                $this->tagValueStorage[$tagName] = $tagValue;
            }

            $tags[$tagName] = $tagValue;
        }
        return md5(serialize($tags));
    }

    public function flushByTag($tagName)
    {
        //AHU: change value in tag instead of flushing all items
        $tagName = $this->computeCacheTagName($tagName);
        $tagValue = time();
        $this->getCollection()->set($tagName, $tagValue, 3600*24);
        $this->tagValueStorage[$tagName] = $tagValue;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys, array $tags = array())
    {
        //return $this->getCollection()->get($this->computeCacheKeys($keys));
        $result =  $this->getCollection()->get($this->computeCacheKeys($keys, $tags));

        if ((false !== $result) && ($result instanceof \Sonata\CacheBundleAlt\Cache\CacheElement)) {
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