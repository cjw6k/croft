<?php
/**
 * The Router class is herein defined.
 *
 * @package	WebFoo\RouterFoo
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\RouterFoo;

use \FastRoute\Dispatcher;
use \FastRoute\RouteCollector;

use \A6A\Aether\Aether;
use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Router\Route;
use \cjw6k\WebFoo\Router\RouterInterface;

/**
 * The Router service determines which object method will handle incoming request
 */
class Router implements RouterInterface
{

	use Aether;

	/**
	 * Store a local reference to the active configuration and current request
	 *
	 * @param ConfigInterface  $config  The active configuration.
	 * @param RequestInterface $request The current request.
	 */
	public function __construct(ConfigInterface $config, RequestInterface $request)
	{
		$this->setConfig($config);
		$this->setRequest($request);
	}

	/**
	 * Map a request to a specific class and method, with path parameters
	 *
	 * @return mixed The class, method and parameters that are matched to the request.
	 */
	public function route()
	{
		$routes = $this->getRoutes();
		$route_collector = function(RouteCollector $route_collector) use($routes) : void {
			if(empty($routes)){
				return;
			}

			$safe_routes = array();
			foreach($routes as $route){
				if(!($route instanceof Route)){
					// throw exception
					continue;
				}
				$safe_routes[] = $route;
			}

			if(empty($safe_routes)){
				return;
			}

			usort(
				$safe_routes,
				function(Route $route_a, Route $route_b){
					if ($route_a->getPriority() == $route_b->getPriority()) {
						return 0;
					}
					return ($route_a->getPriority() > $route_b->getPriority()) ? -1 : 1;
				}
			);

			foreach($safe_routes as $route){
				list($verbs, $regex, $data) = $route->pack();
				$route_collector->addRoute($verbs, $regex, $data);
			}
		};

		$route_collector_opts = array(
			'cacheFile' => VAR_ROOT . '.route-cache',
			'cacheDisabled' => isset($this->getConfig()->getRouting()['use_cache']) ? $this->getConfig()->getRouting()['use_cache'] : true,
		);

		$this->setDispatcher(
			\FastRoute\cachedDispatcher(
				$route_collector,
				$route_collector_opts
			)
		);

		$match = $this->getDispatcher()->dispatch(
			$this->getRequest()->getMethod(),
			$this->getRequest()->getPath()
		);

		return $this->_processRoute($match);
	}

	/**
	 * Catch invalid requests and map to appropriate error handlers
	 *
	 * @param mixed $matched_route The route which matched the current request.
	 *
	 * @return mixed The class, method and parameters that are matched to the request.
	 */
	private function _processRoute($matched_route)
	{
		// trigger_error(print_r(array($this->getRequest()->getPath(), $matched_route), true));
		switch($matched_route[0]){
			case Dispatcher::METHOD_NOT_ALLOWED:
				return array(null, '_sling405', array('use_vars' => true), $matched_route[1]);

			case Dispatcher::NOT_FOUND:
				return array(null, '_sling404', null, null);

			case Dispatcher::FOUND:
				return array_merge($matched_route[1], array($matched_route[2]));
		}
	}

}
