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

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

use Sonata\CacheAltBundle\Cache\CacheInterface;
use Sonata\CacheAltBundle\Cache\CacheElement;

class RedisCache implements CacheInterface
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
        return $this->getCollection()->flushAll();
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
            $this->collection = new \Redis();

            $this->collection->connect($this->servers[0]['host'], $this->servers[0]['port'], $this->servers[0]['timeout']);

			//AHU: TODO: add this to configuration
			 $this->collection->select(1);

            //TODO use distributed Redis Array (another class) if multiple domains are available
//            foreach ($this->servers as $server) {
//                $this->collection->addServer($server['host'], $server['port'], $server['persistent'], $server['weight']);
//            }
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array(), array $tags = array())
    {
        $collection = $this->getCollection();

        $collection->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        $key = $this->computeCacheKeys($keys, $tags);

        $cacheElement = new CacheElement($keys, $data, $ttl);
        $collection->setex(
            $key,
            /*time() +*/ $cacheElement->getTtl(),
            $cacheElement
        );

        foreach ($tags as $tag) {
            $collection->sAdd($tag , $key);
        }

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

    private function removeCacheKeyPrefix(array $keys)
    {
        $prefixStrLen = strlen($this->prefix);

        foreach($keys as $k => $keyWithPrefix){
            $keys[$k] = substr($keyWithPrefix, $prefixStrLen);
        }

        return $keys;
    }

    private function computeCacheTagName($tag)
    {
        return $this->prefix.'TAG_'.md5($tag);
    }

    private function computeCacheTags($tagNames)
    {
        if (empty($tagNames)) {
            return;
        }

        //AHU support for tags in redis
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
                    $tagValue = time();
                    $this->getCollection()->setex($tagName, 3600*24, $tagValue);
                }
                $this->tagValueStorage[$tagName] = $tagValue;
            }

            $tags[$tagName] = $tagValue;
        }
        return md5(serialize($tags));
    }

    public function flushByTag($tagName)
    {
        /*
        //AHU: change value in tag instead of flushing all items
        $tagName = $this->computeCacheTagName($tagName);
        $tagValue = time();
        $this->getCollection()->setex($tagName, 3600*24, $tagValue);
        $this->tagValueStorage[$tagName] = $tagValue;
         */

        $tag = $this->prefix.'TAG_'.$tagName;

        $collection = $this->getCollection();

        $keys = $collection->sMembers($tag);
        if (!is_array($keys)) {
            return false;
        }

        $keys[] = $tag;

        //delete all keys and tag
        $collection->delete($keys);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys, array $tags = array())
    {
        $this->getCollection()->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        $key = $this->computeCacheKeys($keys, $tags);

        //return $this->getCollection()->get($this->computeCacheKeys($keys));
        $result =  $this->getCollection()->get($key);

        if ((false !== $result) && ($result instanceof \Sonata\CacheAltBundle\Cache\CacheElement)) {
            return $result;
        }

        return false;
    }








    //BETA FUNKCIE
    //hromadna incrementacia viacerych poloziek
    public function incrementMulti(array $keys, $value, $ttl, array $tags = array())
    {
        $collection = $this->getCollection();

        $multi = $collection->multi();

        if (!$multi) {
            throw new RuntimeException('Calling multi() on Redis failed.');
        }

        foreach ($keys as $key) {

            //computeCache neriesime, pretoze ako kluc nepotrebujeme md5 hash ale normalny text
            $key = $this->prefix.$key;
            if ($value>1) {
                $multi->incrBy($key, $value);
            } else {
                $multi->incr($key);
            }
            $multi->setTimeout($key, $ttl);

            foreach ($tags as $tag) {
                $tag = $this->prefix.'TAG_'.$tag;
                $multi->sAdd($tag , $key);
                $multi->setTimeout($tag , 3600*24*7);//TODO tagy by sa tiez mali  po nejakej dobe clearovat samy z cache ak nie su pouzivane, mali by vzdy nadobudat max. moznu dobu nejakej cache
                //vzdy ked sa nieco mihne v tomto tagu tak zvysime jeho zivotnost
            }
        }

        $multi->exec();

        return true;
    }

//    public function getNumeric($key, array $tags = array())
//    {
//        //computeCache neriesime, pretoze ako kluc nepotrebujeme md5 hash ale normalny text
//        $key = $this->prefix.$key;
//        $result =  $this->getCollection()->get($key);
//
//        if ((false !== $result)) {
//            return (int) $result;
//        }
//
//        return false;
//    }

    /**
     * Get array of all items (key=value) by tag
     *
     * @param string $tag
     * @return array|false
     */
    public function getByTag($tag)
    {
        $tag = $this->prefix.'TAG_'.$tag;
        $collection = $this->getCollection();
        $collection->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        $keys = $collection->sMembers($tag);
        if (!is_array($keys)) {
            return false;
        }

        $multi = $collection->multi();
        foreach ($keys as $key) {
            $multi->get($key);
        }

        $values = $multi->exec(); //non-asociative array

        $keyCount = count($keys);
        if ($keyCount != count($values)) {
            throw new \RuntimeException('RedisCache: Count of key and values does not match');
        }

        if ($keyCount>0) {
            return array_combine($this->removeCacheKeyPrefix($keys), $values);
        }

        return false;
    }



    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}