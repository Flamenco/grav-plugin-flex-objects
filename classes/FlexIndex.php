<?php
namespace Grav\Plugin\FlexObjects;

use Grav\Framework\Object\Interfaces\ObjectCollectionInterface;
use Grav\Framework\Object\Interfaces\ObjectInterface;
use Grav\Plugin\FlexObjects\Collections\ArrayIndex;
use PSR\SimpleCache\InvalidArgumentException;

class FlexIndex extends ArrayIndex // implements ObjectCollectionInterface
{
    /** @var FlexDirectory */
    private $flexDirectory;

    /**
     * Initializes a new IndexCollection.
     *
     * @param array $entries
     * @param FlexDirectory $flexDirectory
     */
    public function __construct(array $entries, FlexDirectory $flexDirectory)
    {
        parent::__construct($entries);

        $this->flexDirectory = $flexDirectory;
    }

    /**
     * @return FlexDirectory
     */
    public function getFlexDirectory()
    {
        return $this->flexDirectory;
    }

    /**
     * @param bool $prefix
     * @return string
     */
    public function getType($prefix = true)
    {
        $type = $prefix ? $this->getTypePrefix() : '';

        return $type . $this->flexDirectory->getType();
    }

    /**
     * @return string[]
     */
    public function getStorageKeys()
    {
        // Get storage keys for the objects.
        $keys = [];
        foreach ($this->getEntries() as $key => $value) {
            $keys[\is_array($value) ? $value[0] : $key] = $key;
        }

        return $keys;
    }

    /**
     * @return int[]
     */
    public function getTimestamps()
    {
        // Get storage keys for the objects.
        $timestamps = [];
        foreach ($this->getEntries() as $key => $value) {
            $timestamps[$key] = \is_array($value) ? $value[1] : $value;
        }

        return $timestamps;
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->getType(true) . '.' . sha1(json_encode($this->getKeys()));
    }

    /**
     * @return string
     */
    public function getCacheChecksum()
    {
        return sha1($this->getCacheKey() . json_encode($this->getTimestamps()));
    }

    /**
     * {@inheritDoc}
     */
    public function call($method, array $arguments = [])
    {
        /** @var FlexCollection $className */
        $className = $this->flexDirectory->getObjectClass();
        $cachedMethods = $className::getCachedMethods();

        if (!empty($cachedMethods[$method])) {
            $key = $this->getType(true) . '.call.' . sha1($method . json_encode($arguments) . $this->getCacheKey());

            $cache = $this->flexDirectory->getCache('object');

            $test = new \stdClass;
            try {
                $result = $cache->get($key, $test);
            } catch (InvalidArgumentException $e) {
                $result = $test;
            }

            if ($result === $test) {
                $result = $this->loadCollection()->call($method, $arguments);

                try {
                    $cache->set($key, $result);
                } catch (InvalidArgumentException $e) {
                    // TODO: log error.
                }
            }
        } else {
            $result = $this->loadCollection()->call($method, $arguments);
        }

        return $result;
    }

    public function __call($name, $arguments)
    {
        /** @var FlexCollection $className */
        $className = $this->flexDirectory->getCollectionClass();
        $cachedMethods = $className::getCachedMethods();

        if (!empty($cachedMethods[$name])) {
            $key = $this->getType(true) . '.' . $name . '.' . sha1($name . json_encode($arguments) . $this->getCacheKey());

            $cache = $this->flexDirectory->getCache('object');

            $test = new \stdClass;
            try {
                $result = $cache->get($key, $test);
            } catch (InvalidArgumentException $e) {
                $result = $test;
            }

            if ($result === $test) {
                $result = $this->loadCollection()->{$name}(...$arguments);

                try {
                    $cache->set($key, $result);
                } catch (InvalidArgumentException $e) {
                    // TODO: log error.
                }
            }
        } else {
            $result = $this->loadCollection()->{$name}(...$arguments);
        }

        return $result;
    }

    /**
     * @param array $entries
     * @param array $indexes
     * @return static
     */
    protected function createFrom(array $entries)
    {
        return new static($entries, $this->flexDirectory);
    }

    /**
     * @return string
     */
    protected function getTypePrefix()
    {
        return 'i.';
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return ObjectInterface|null
     */
    protected function loadObject($key, $value)
    {
        $objects = $this->flexDirectory->loadObjects([$key => $value]);

        return $objects ? reset($objects) : null;
    }

    /**
     * @param array|null $entries
     * @return ObjectInterface[]
     */
    protected function loadObjects(array $entries = null)
    {
        return $this->flexDirectory->loadObjects($entries ?? $this->getEntries());
    }

    /**
     * @param array|null $entries
     * @return ObjectCollectionInterface
     */
    protected function loadCollection(array $entries = null)
    {
        return $this->flexDirectory->loadCollection($entries ?? $this->getEntries());
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isAllowedObject($value)
    {
        return $value instanceof FlexObject;
    }
}
