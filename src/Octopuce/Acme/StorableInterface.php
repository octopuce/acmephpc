<?php

namespace Octopuce\Acme;

interface StorableInterface
{
    /**
     * Get Id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get type
     *
     * @return string
     */
    //public function getType();

    /**
     * Returns an associative array (name => value) of data to be persisted
     *
     * @return array
     */
    public function getStorableData();
}
