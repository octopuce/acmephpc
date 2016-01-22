<?php

namespace Octopuce\Acme\Storage;

class Factory
{
    /**
     * Adapters
     *
     * @var array
     */
    private $adapters = array();

    /**
     * Constructor
     *
     * @param array $adapters
     */
    public function __construct(array $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * Create
     *
     * @param string $adapter
     *
     * @return StorageInterface
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function create($adapter)
    {
        if (!array_key_exists($adapter, $this->adapters)) {
            throw new \InvalidArgumentException(sprintf('Adapter %s is not set', $adapter));
        }

        $adapter = $this->adapters[$adapter]->__invoke();

        if (!$adapter instanceof StorageInterface) {
            throw new UnexpectedValueException('The storage adapter must implement \Octopuce\Storage\StorageInterface');
        }

        return $adapter;
    }

}
