<?php
/**
 * The Media class is herein defined.
 *
 * @package	WebFoo\MediaFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\MediaFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Media\MediaInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Media class slings multimedia content
 */
class Media implements MediaInterface, Routable
{

	use Aether;

	/**
	 * Store a local reference to the response
	 *
	 * @param ResponseInterface $response The response.
	 * @param StorageInterface  $storage  The storage service.
	 */
	public function __construct(ResponseInterface $response, StorageInterface $storage)
	{
		$this->setResponse($response);
		$this->setStorage($storage);
	}

	/**
	 * Provides a list of routes to register with the Router to be serviced by this service.
	 *
	 * @return mixed|null The list of routes to register or null if there are none.
	 */
	public function getRoutes()
	{
		return array(
			new Route('GET', '/{year:[0-9]{4}}/{month:0[0-9]|1[0-2]}/{day:(?:[012][0-9])|3[0-1]}/{post_id:[0-9]+}/media/{media:.*}', 'sling', array('use_vars' => true)),
		);
	}

	/**
	 * Control content-media requests
	 *
	 * @param string[] $vars The hash of path components in the content request.
	 *
	 * @return string[]|void The template to render, with alternate, or void to skip rendering.
	 */
	public function sling(array $vars)
	{
		$filename = array_pop($vars);
		$post_record_path = implode('/', $vars) . '/';
		$path = CONTENT_ROOT . $post_record_path . 'media/' . $filename;

		if(!file_exists($path)){
			$this->getResponse()->setCode(404);
			return array('404.php', 'default.php');
		}

		$this->getResponse()->mergeHeaders('Content-Type: ' . mime_content_type($path));
		readfile($path);
	}

}
