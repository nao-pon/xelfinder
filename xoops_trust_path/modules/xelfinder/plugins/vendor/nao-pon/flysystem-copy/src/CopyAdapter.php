<?php

namespace League\Flysystem\Copy;

use Barracuda\Copy\API;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;

class CopyAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    /**
     * Result key map.
     *
     * @var array
     */
    protected static $resultMap = [
        'size'           => 'size',
        'mime_type'      => 'mimetype',
        'type'           => 'type',
    ];

    /**
     * Copy API.
     *
     * @var API
     */
    protected $client;

    /**
     * Object meta data cache array
     * 
     * @var array
     */
    private $metaCache = [];

    /**
     * Constructor.
     *
     * @param API    $client
     * @param string $prefix
     */
    public function __construct(API $client, $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
        $this->metaCache = [];
    }

    /**
     * Check weather a file exists.
     *
     * @param string $path
     *
     * @return array|false false or file metadata
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromString($location, $contents);

        unset($this->metaCache[$location]);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromStream($location, $resource);

        unset($this->metaCache[$location]);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromString($location, $contents);

        unset($this->metaCache[$location]);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromStream($location, $resource);

        unset($this->metaCache[$location]);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->client->readToString($location);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->client->readToStream($location);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        if (! $this->client->rename($location, $destination)) {
            return false;
        }

        unset($this->metaCache[$location], $this->metaCache[$destination]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($location, $destination);
        } catch (\Exception $e) {
            return false;
        }

        unset($this->metaCache[$destination]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        unset($this->metaCache[$location]);

        return $this->client->removeFile($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        $location = $this->applyPathPrefix($path);

        unset($this->metaCache[$location]);

        return $this->client->removeDir($location);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($path, Config $config)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $this->client->createDir($location);
        } catch (\Exception $e) {
            return false;
        }

        unset($this->metaCache[$location]);

        return compact('path') + ['type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);

        if (!empty($this->metaCache[$location])) {
            return $this->normalizeObject($this->metaCache[$location], $location);
        }
        $this->metaCache[$location] = $object = $this->client->getMeta($location);

        if ($object === false || empty($object)) {
            return false;
        }

        if (!empty($object->children)) {
            foreach($object->children as $child) {
                if (!isset($this->metaCache[$child->path])) {
                    $this->metaCache[$child->path] = $child;
                }
            }
        }
        
        return $this->normalizeObject($object, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($dirname = '', $recursive = false)
    {
        $listing = [];
        $location = $this->applyPathPrefix($dirname);

        if (isset($this->metaCache[$location]) && isset($this->metaCache[$location]->children)) {
            $object = $this->metaCache[$location];
        } else {
            $this->metaCache[$location] = $object = $this->client->getMeta($location);
        }

        if (!empty($object->children)) {
            foreach($object->children as $child) {
                $listing[] = $this->normalizeObject($child, $child->path);
                if (!isset($this->metaCache[$child->path])) {
                    $this->metaCache[$child->path] = $child;
                }
                if ($recursive && $child->type == 'dir') {
                    $listing = array_merge($listing, $this->listContents($child->path, $recursive));
                }
            }
        }

        return $listing;
    }

    /**
     * Get item absolute URL
     *
     * @param string   $path
     *
     * @return string item absolute URL
     */
    public function getUrl($path)
    {
        $location = $this->applyPathPrefix($path);

        try {
            $object = $this->getMetadata($path);
            if (! isset($object->links)) {
                $this->metaCache[$location] = $object = $this->client->getMeta($location);
            }
            $url = '';
            if (! empty($object->links)) {
                foreach($object->links as $link) {
                    if ($link->status === 'viewed' && $link->permissions === 'read') {
                        $url = $link->url;
                        break;
                    }
                }
            }
            if ($url === '') {
                $object = $this->client->createLink($location);
                $url = $object->url;
            }
            return $url . '/' . rawurlencode($object->name);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Normalize a result from Copy.
     *
     * @param stdClass $object
     * @param string   $path
     *
     * @return array|false file metadata
     */
    protected function normalizeObject($object, $path)
    {
        if (is_a($object, 'stdClass') === false) {
            return false;
        }

        if (isset($object->modified_time)) {
            $timestamp = $object->modified_time;
        }

        $path = trim($this->removePathPrefix($path), '/');
        $result = Util::map((array) $object, static::$resultMap);

        return compact('timestamp', 'path') + $result;
    }

    /**
     * Apply the path prefix.
     *
     * @param string $path
     *
     * @return string prefixed path
     */
    public function applyPathPrefix($path)
    {
        $prefixed = parent::applyPathPrefix($path);

        return '/'.ltrim($prefixed, '/');
    }
}
