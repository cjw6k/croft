<?php
/**
 * The Page class is herein defined.
 *
 * @package	WebFoo\PageFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\PageFoo;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Page\PageInterface;
use \cjw6k\WebFoo\Response\ResponseInterface;
use \cjw6k\WebFoo\Router\Routable;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Storage\StorageInterface;

/**
 * The Page class slings page templates
 */
class Page implements PageInterface, Routable
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
			new Route('GET', '/', 'home'),
			new Route('GET', '/{page}/{category}[/]', 'sling', array('use_vars' => true), null, 0),
			new Route('GET', '/{page}[/]', 'sling', array('use_vars' => true), null, 0),
		);
	}

	/**
	 * Control requests to the homepage
	 *
	 * @return string[] The template to render, with alternate.
	 */
	public function home()
	{
		return array('home.php', 'default.php');
	}

	/**
	 * Control page template requests
	 *
	 * @param string[] $vars The hash of path components in the page request.
	 *
	 * @return string[] The template to render, with alternate.
	 */
	public function sling(array $vars)
	{
		/**
		 * The Router will not start this action unless page is set
		 *
		 * @psalm-suppress PossiblyUndefinedStringArrayOffset
		 */
		$page = $vars['page'];
		$category = isset($vars['category']) ? $vars['category'] : null;

		$this->setCategory($category);

		$template = realpath(TEMPLATES_LOCAL . 'pages/' . $page . '.php');
		if(0 !== strpos($template, realpath(TEMPLATES_LOCAL))){
			$this->getResponse()->setCode(404);
			return array('404.php', 'default.php');
		}

		if(!file_exists($template)){
			$this->getResponse()->setCode(404);
			return array('404.php', 'default.php');
		}

		return array('pages/' . $page . '.php', 'default.php');
	}

}
