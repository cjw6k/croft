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

}
