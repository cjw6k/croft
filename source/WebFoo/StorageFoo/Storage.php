<?php
/**
 * The Storage class is herein defined.
 *
 * @package	WebFoo\StorageFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\StorageFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Storage\Segment;
use \cjw6k\WebFoo\Storage\StorageInterface;
use \cjw6k\WebFoo\Storage\Store;

/**
 * The Storage service loads and stores data.
 */
class Storage implements StorageInterface
{

	use Aether;

	/**
	 * The registered stores.
	 *
	 * @var mixed[]
	 */
	private $_stores = array();

	/**
	 * Store a local reference to the active configuration
	 *
	 * @param ConfigInterface $config The active configuration.
	 */
	public function __construct(ConfigInterface $config)
	{
		$this->setConfig($config);
	}

	/**
	 * Register a prefix in for storage in segment
	 *
	 * @param Store $store The store definition.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 */
	public function register(Store $store)
	{
		list($segment, $prefix, $ingress, $egress) = $store->pack();

		// @phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if(!(Segment::hasValue($segment))){
			// throw exception
		}
		// @phpcs:enable Generic.CodeAnalysis.EmptyStatement.DetectedIf

		$this->_stores[$segment][$prefix] = array(
			'ingress' => $ingress,
			'egress' => $egress,
		);
	}

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
	public function hasIndex(int $segment, string $prefix, string $index)
	{
		if(!isset($this->_stores[$segment][$prefix])){
			// throw exception
			return false;
		}

		$path = $this->_getPathFromSegment($segment);
		if(empty($path)){
			// throw exception
			return false;
		}

		return file_exists($path . $prefix . '/' . $index);
	}

	/**
	 * Store data into a storage segment at the given prefixed index
	 *
	 * WARNING: will overwrite stored data with no notice.
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 * @param mixed   $data    The data to store.
	 *
	 * @return void
	 */
	public function store(int $segment, string $prefix, string $index, $data)
	{
		// WARNING: will overwrite stored data without any notice
		$path = $this->_getPathFromSegment($segment);
		if(empty($path)){
			// throw exception
			return;
		}

		$ingress = $this->_stores[$segment][$prefix]['ingress'];
		$content = is_null($ingress) ? $data : $ingress($data);

		$parent_path = dirname($path . $prefix . '/' . $index);
		if(!file_exists($parent_path)){
			mkdir($parent_path, 0755, true);
		}

		file_put_contents($path . $prefix . '/' . $index, $content);
	}

	/**
	 * Load data from a storage segment at the given prefixed index.
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 *
	 * @return mixed|null The data from storage or null if the prefixed index is not set.
	 */
	public function load(int $segment, string $prefix, string $index)
	{
		if(!$this->hasIndex($segment, $prefix, $index)){
			return null;
		}

		$path = $this->_getPathFromSegment($segment);
		if(empty($path)){
			// throw exception
			return null;
		}

		$egress = $this->_stores[$segment][$prefix]['egress'];
		$content = file_get_contents($path . $prefix . '/' . $index);

		return is_null($egress) ? $content : $egress($content);
	}

	/**
	 * Map a given storage segment to a path on disk.
	 *
	 * @param integer $segment The storage segment.
	 *
	 * @return string|null The storage path on disk, or null on failure.
	 */
	private function _getPathFromSegment(int $segment)
	{
		switch($segment){
			case Segment::SYSTEM:
				// $path = $this->getConfig()->getStorage()['system'];
				return defined('VAR_ROOT') ? VAR_ROOT : null;

			case Segment::CONTENT:
				// $path = $this->getConfig()->getStorage()['content'];
				return defined('CONTENT_ROOT') ? CONTENT_ROOT : null;

			default:
				// throw exception
				return null;
		}
	}

	/**
	 * Lock read/write operations on storage at the given index of the prefixed storage segment
	 *
	 * @param integer $segment   The storage segment.
	 * @param string  $prefix    The prefix.
	 * @param string  $index     The index.
	 * @param integer $operation See flock().
	 *
	 * @return mixed|null A handle for the lock or null on failure.
	 */
	public function lock(int $segment, string $prefix, string $index, int $operation)
	{
		$path = $this->_getPathFromSegment($segment);
		if(empty($path)){
			// throw exception
			return null;
		}

		$parent_path = dirname($path . $prefix . '/' . $index);
		if(!file_exists($parent_path)){
			mkdir($parent_path, 0755, true);
		}

		$lock_file = fopen($path . $prefix . '/' . $index, 'c+');
		if(!flock($lock_file, $operation)){
			fclose($lock_file);
			return null;
		}

		return $lock_file;
	}

	/**
	 * Unock read/write operations on storage at the given index of the prefixed storage segment
	 *
	 * @param mixed $lock The handle for the lock.
	 *
	 * @return void
	 */
	public function unlock($lock)
	{
		/**
		 * This is actually needed lol. Remove the suppression if you don't believe it.
		 *
		 * @psalm-suppress UnusedFunctionCall
		 */
		flock($lock, LOCK_UN);
		fclose($lock);
	}

	/**
	 * Delete data from the prefix storage segment at the given index
	 *
	 * @param integer $segment The storage segment.
	 * @param string  $prefix  The prefix.
	 * @param string  $index   The index.
	 *
	 * @return null|void Null if the index is not in use on the prefixed storage segment.
	 */
	public function purge(int $segment, string $prefix, string $index)
	{
		$path = $this->_getPathFromSegment($segment);
		if(empty($path)){
			// throw exception
			return null;
		}

		unlink($path . $prefix . '/' . $index);
	}

}
