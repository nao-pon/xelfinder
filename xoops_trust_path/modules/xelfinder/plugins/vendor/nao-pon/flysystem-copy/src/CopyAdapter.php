<?php

namespace League\Flysystem\Copy;

use Barracuda\Copy\API;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;
use League\Flysystem\Util;
use stdClass;

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
     * Constructor.
     *
     * @param API    $client
     * @param string $prefix
     */
    public function __construct(API $client, $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }
    
    /**
     * Get the Copy API instance.
     *
     * @return API
     */
    public function getClient()
    {
        return $this->client;
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

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromStream($location, $resource);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromString($location, $contents);

        return $this->normalizeObject($result, $location);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $result = $this->client->uploadFromStream($location, $resource);

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

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $origin = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($origin, $destination);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->client->removeFile($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($path)
    {
        $location = $this->applyPathPrefix($path);

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

        return compact('path') + ['type' => 'dir'];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);
        $object = $this->client->getMeta($location);

        if ($object === false) {
            return false;
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

        if (! $result = $this->client->listPath($location)) {
            return [];
        }

        $location = rtrim($location, '/');
        foreach ($result as $object) {
            if ($location !== $object->path) {
                $listing[] = $this->normalizeObject($object, $object->path);

                if ($recursive && $object->type == 'dir') {
                    $listing = array_merge($listing, $this->listContents($object->path, $recursive));
                }
            }
        }

        return $listing;
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
        if (!$object instanceof stdClass) {
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
