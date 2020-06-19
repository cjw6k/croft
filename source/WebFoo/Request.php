<?php
/**
 * The Request class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Request class provides an interface to the current HTTP request
 */
class Request
{

	use Aether;

	/**
	 * Parse the request URI
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function __construct()
	{
		if(null == $this->server('REQUEST_URI')){
			return;
		}

		if(null != $this->server('REQUEST_METHOD')){
			$this->setMethod($this->server('REQUEST_METHOD'));
		}

		$url_parts = parse_url($this->server('REQUEST_URI'));

		if(isset($url_parts['path'])){
			$this->setPath(rawurldecode($url_parts['path']));
		}

		if(isset($url_parts['query'])){
			$this->setQuery($url_parts['query']);
		}
	}

	/**
	 * Provide data from $_SESSION at specified index
	 *
	 * @param string $index The index of the data in $_SESSION.
	 * @param mixed  $data  The data to store in $_SESSION at specified index.
	 *
	 * @return mixed The data from $_SESSION or null if not set.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function session(string $index, $data = null)
	{
		if(null !== $data){
			$_SESSION[$index] = $data;
			return $data;
		}

		if(!isset($_SESSION[$index])){
			return null;
		}

		return $_SESSION[$index];
	}

	/**
	 * Provide data from $_SERVER at specified index
	 *
	 * @param string $index The index of the data in $_SERVER.
	 *
	 * @return mixed The data from $_SERVER or null if not set.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function server(string $index)
	{
		if(!isset($_SERVER[$index])){
			return null;
		}

		return $_SERVER[$index];
	}

	/**
	 * Filter and provide data from $_GET at specified index
	 *
	 * @param string $index The index of the data in $_GET.
	 *
	 * @return mixed The data from $_GET or null if not set.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function get(string $index)
	{
		if(!isset($_GET[$index])){
			return null;
		}

		return filter_input(INPUT_GET, $index);
	}

	/**
	 * Filter and provide data from $_POST at specified index
	 *
	 * If no index is specified, returns the full $_POST array.
	 *
	 * @param string|null $index The index of the data in $_POST.
	 *
	 * @return mixed The data from $_POST or null if not set.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function post(string $index = null)
	{
		if(is_null($index)){
			$set = array();
			foreach(array_keys($_POST) as $key){
				$set[$key] = $this->post($key);
			}
			return $set;
		}

		if(!isset($_POST[$index])){
			return null;
		}

		return is_array($_POST[$index]) ? filter_input(INPUT_POST, $index, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) : filter_input(INPUT_POST, $index);
	}

	/**
	 * Filter and provide data from $_COOKIE at specified index
	 *
	 * @param string $index The index of the data in $_COOKIE.
	 *
	 * @return mixed The data from $_COOKIE or null if not set.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function cookie(string $index)
	{
		if(!isset($_COOKIE[$index])){
			return null;
		}

		return filter_input(INPUT_COOKIE, $index);
	}

}
