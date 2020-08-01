<?php
/**
 * The Router\Routable interface is herein defined.
 *
 * @package	WebFoo\Router
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Router;

/**
 * The Routable interface
 *
 * A routable class has an opportunity to register routes with the router and optionally provide
 * methods to service those routes.
 */
interface Routable
{

	/**
	 * Provides a list of routes to register with the Router to be serviced by this object.
	 *
	 * @return mixed|null The list of routes to register or null if there are none.
	 */
	public function getRoutes();

}
