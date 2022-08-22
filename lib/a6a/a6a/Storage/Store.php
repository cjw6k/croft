<?php

namespace a6a\a6a\Storage;

/**
 * The Storage\Store class is a helper to make working with stores easier
 */
class Store
{
    /**
     * The packaged store.
     */
    private mixed $store = null;

    /**
     * Combine _params_ into a store definition
     *
     * @param int $segment The segment of storage to use.
     * @param string $prefix The key prefix for all keys in this store.
     * @param callable|null $ingress The ingress method.
     * @param callable|null $egress The egress method.
     */
    public function __construct(int $segment, string $prefix, ?callable $ingress = null, ?callable $egress = null)
    {
        $this->store = [
            $segment,
            $prefix,
            $ingress,
            $egress,
        ];
    }

    /**
     * Package the store details for registration by the storage service
     *
     * @return mixed The store in a form fit for storage.
     */
    public function pack(): mixed
    {
        return $this->store;
    }
}
