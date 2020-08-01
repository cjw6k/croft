<?php
/**
 * The StorageInterface class is herein defined.
 *
 * @package	WebFoo\Storage
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Storage;

use \Exception;

use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;

interface StorageInterface extends ServiceInterface
{

	/**
	 * Store a local reference to the active configuration
	 *
	 * @param ConfigInterface $config The active configuration.
	 *
	 * @throws Exception The method has not been implemented.
	 */
	public function __construct(ConfigInterface $config);

	/**
	 * Register a prefix in for storage in segment
	 *
	 * @param Store $store The store definition.
	 *
	 * @return void
	 */
	public function register(Store $store);

	/**
	 * Check if a storage segment has data at the given prefixed index.
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 *
	 * @return boolean True  The index is set on this prefix in the storage segmeent.
	 *                 False The index is not set on this prefix in the storage segment.
	 */
	public function hasIndex(int $segment, string $prefix, string $index);

	/**
	 * Store data into a storage segment at the given prefixed index.
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 * @param mixed   $data    The data to store.
	 *
	 * @return void
	 */
	public function store(int $segment, string $prefix, string $index, $data);

	/**
	 * Load data from a storage segment at the given prefixed index.
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 *
	 * @return mixed|null The data from storage or null if the prefixed index is not set.
	 */
	public function load(int $segment, string $prefix, string $index);

}
