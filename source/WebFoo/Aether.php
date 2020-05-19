<?php
/**
 * The WebFoo\Aether trait is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

use \Exception;

/**
 * Aether provides data storage via get/set methods handled by the magic __call method
 *
 * It also provides some commonly used string manipulation functions to convert to and from class
 * and method names.
 */
trait Aether
{

	/**
	 * The data store for general usage with getN and setN methods
	 *
	 * @var	mixed[]	$_data
	 */
	private $_data = array();

	/**
	 * The data store for booleans used with isN
	 *
	 * @var	bool[]	$_flags
	 */
	private $_flags = array();

	/**
	 * Get the data from the data store at the indicated position or get the full data store if no position is given
	 *
	 * @param string|integer $key An array index to the data store.
	 *
	 * @return mixed|null	The data from the array, the whole array or null if the indicated position does not exist
	 */
	public function getData($key = '')
	{
		if(empty($key)){
			return $this->_data;
		}
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	/**
	 * Set the data into the array at the indicated position and return the instance
	 *
	 * @param string|integer $key  An array index to the data store.
	 * @param mixed          $data The data or object reference to put into the data store at the indicated index.
	 *
	 * @return self	Allows chaining the setN methods
	 */
	public function setData($key, $data)
	{
		$this->_data[$key] = $data;

		return $this;
	}

	/**
	 * Check if the instance has the indicated data, or any data if the parameter is empty
	 *
	 * @param string|integer $key An array index to the data store.
	 *
	 * @return boolean True	 If i) $key is empty and any data is stored; or ii) data exists at the indicated position.
	 *			       False If i) $key is empty and the data store is empty; or ii) data does not exist at the indicated position.
	 */
	public function hasData($key = '')
	{
		if(empty($key)){
			return !empty($this->_data);
		}
		return array_key_exists($key, $this->_data);
	}

	/**
	 * Unset the data from the array at the indicated position, or all data if the parameter is empty and return the instance
	 *
	 * @param string|integer $key An array index to the data store.
	 *
	 * @return self	Allows chaining the setN methods
	 */
	public function unsetData($key = '')
	{
		if(empty($key)){
			$this->_data = array();
			return $this;
		}

		unset($this->_data[$key]);

		return $this;
	}

	/**
	 * Merge new data at the indicated position, and return the instance
	 *
	 * @param string|integer $key  An array index to the data store.
	 * @param mixed          $data The data or object reference to merge into the data store at the indicated index.
	 *
	 * @return mixed the new data at the given position in the data store
	 */
	public function mergeData($key, $data)
	{
		if(!isset($this->_data[$key])){
			$this->setData($key, array($data));

			return $data;
		}

		$this->_data[$key][] = $data;

		return $this->getData($key);
	}

	/**
	 * Check if a boolean data entry in the array is set, or set it
	 *
	 * @param string|integer $key  An array index to the data store.
	 * @param boolean|null   $bool If not null, the new data to store in the indicated location.
	 *
	 * @return boolean|null True  If $bool is null and the flag in $key is true.
	 *					    False If $bool is null and the flag in $key is false.
	 *					    null  If $bool is not null.
	 */
	public function isFlag($key, $bool = null)
	{
		if(null === $bool){
			return isset($this->_flags[$key]) ? $this->_flags[$key] : false;
		}
		$this->_flags[$key] = $bool;
	}

	/**
	 * Try a useful behaviour, if the requested method is not defined on $this
	 *
	 * The magic method \_\_call will run if a method which isn't defined in this object (or objects which extend this one) is called.
	 * \_\_call is used to provide utility functions of the form getN, setN, unsetN, unsN, isN, hasN where N is any valid array index label
	 *
	 * @param string  $requested_method	The method that was called for, which does not exist.
	 * @param mixed[] $args				The arguments that were supplied to the method call.
	 *
	 * @return mixed null, $this, or data from the data store.
	 */
	public function __call(string $requested_method, array $args)
	{
		// Check for a match with the first 5 letters from the requested method
		switch(substr($requested_method, 0, 5)){

			// The requested method matches unsetNNNN
			case 'unset':
				$key = $this->_underscore(substr($requested_method, 5));
				return $this->unsetData($key);

			// The requested method matches mergeNNNN
			case 'merge':
				$key = $this->_underscore(substr($requested_method, 5));
				return $this->mergeData($key, isset($args[0]) ? $args[0] : null);
		}

		// Check for a match with the first 2 letters from the requested method
		switch(substr($requested_method, 0, 2)){

			// The requested method matches isNNNN
			case 'is':
				$key = $this->_underscore(substr($requested_method, 2));
				return $this->isFlag($key, isset($args[0]) ? $args[0] : null);
		}

		return $this->_checkForThreeLetterSpecialMethodPrefix($requested_method, $args);
	}

	/**
	 * Extend the __call check for special methods beginning with three characters
	 *
	 * @param string  $requested_method	The method that was called for, which does not exist.
	 * @param mixed[] $args				The arguments that were supplied to the method call.
	 *
	 * @return mixed|null|self The data from the data store or self for method chaining.
	 *
	 * @throws Exception A requested method is invalid.
	 *
	 * @psalm-suppress MissingReturnType
	 */
	private function _checkForThreeLetterSpecialMethodPrefix(string $requested_method, array $args)
	{
		// Check for a match with the first 3 letters from the requested method
		switch(substr($requested_method, 0, 3)){

			// The requested method matches getNNNN
			case 'get':
				$key = $this->_underscore(substr($requested_method, 3));
				return $this->getData($key);

			// The requested method matches setNNNN
			case 'set':
				$key = $this->_underscore(substr($requested_method, 3));
				return $this->setData($key, isset($args[0]) ? $args[0] : null);

			// The requested method matches the short form of unsetNNNN, unsNNNN
			case 'uns':
				$key = $this->_underscore(substr($requested_method, 3));
				return $this->unsetData($key);

			// The requested method matches hasNNNN
			case 'has':
				$key = $this->_underscore(substr($requested_method, 3));
				return $this->hasData($key);

			// The requested method does not match any of the special methods provided here
			default:
				// Display a 501 Not Implemented error
				throw new Exception('The requested method ' . $requested_method . ' of class ' . get_class($this) . ' does not exist');
		}
	}

	/**
	 * Prepend uppercase letters, after the first position, with underscores and convert to lowercase
	 *
	 * Example: 'TestData' is converted to 'test_data'
	 *
	 * @param string $name The text to be converted.
	 *
	 * @return string The converted text
	 */
	protected function _underscore(string $name)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
	}

}
