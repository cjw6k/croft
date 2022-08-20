<?php
/**
 * The Store class is herein defined.
 *
 * @package WebFoo\Storage
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Storage;

/**
 * The Storage\Store class is a helper to make working with stores easier
 */
class Store
{

    /**
     * The packaged store.
     *
     * @var mixed
     */
    private $_store = null;

    /**
     * Combine _params_ into a store definition
     *
     * @param integer       $segment The segment of storage to use.
     * @param string        $prefix  The key prefix for all keys in this store.
     * @param callable|null $ingress The ingress method.
     * @param callable|null $egress  The egress method.
     */
    public function __construct(int $segment, string $prefix, callable $ingress = null, callable $egress = null)
    {
        $this->_store = array(
        $segment,
        $prefix,
        $ingress,
        $egress,
        );
    }

    /**
     * Package the store details for registration by the storage service
     *
     * @return mixed The store in a form fit for storage.
     */
    public function pack()
    {
        return $this->_store;
    }

}
