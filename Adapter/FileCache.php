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
use RuntimeException;

/**
 * @todo pridat podporu tagov (len nieco jednoduche ako ma memcache)
 */
class FileCache implements CacheInterface
{
    protected $prefix;
    protected $directories;

    public function __construct($prefix, array $directories)
    {
        $this->prefix  = $prefix;
        $this->directories  = $directories;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        throw new RuntimeException('Not implemented yet.'); //delete content of all directories
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array(), array $tags = array())
    {
        $filePath = $this->getFilePath($keys);

        if (!fileExists($filePath)) {
            return false;
        }

        return unlink($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys, array $tags = array())
    {
        return file_exists($this->getFilePath($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array(), array $tags = array())
    {
        $filePath = $this->getFilePath($keys);
        if (!mkdir(dirname($filePath), 0777, true)) {
            return false;
        }

        return file_put_contents($filePath, serialize($data));
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys, array $tags = array())
    {
        $filePath = $this->getFilePath($keys);

        if (!fileExists($filePath)) {
            return false;
        }

        $serializedData = file_get_contents($filePath);

        if (!$serializedData) {
            return false;
        }

        return unserialize($serializedData);
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        throw new RuntimeException('Not implemented yet.');
    }

    private function getFilePath(array $keys)
    {
        $filename = md5($this->prefix.implode('_', $keys));
        $path= substr($filename, 0, 2).'/'.substr($filename, 2, 2).'/'.substr($filename, 4, 2).'/'.$filename;

        $directoryIndex = (count($this->directories) % ord($filename[0]))-1;

        return $this->directories[$directoryIndex].'/'.$path;
    }
}