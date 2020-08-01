<?php
/**
 * The ExtensionInterface interface is herein defined.
 *
 * @package	WebFoo\Extension
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Extension;

use \Exception;

/**
 * The Extension interface
 */
interface ExtensionInterface
{

	/**
	 * Provides a list of routes to register with the Router to be serviced by this extension.
	 *
	 * @return mixed|null The list of routes to register or null if there are none.
	 *
	 * @throws Exception The method has not been implemented.
	 */
	public function getRoutes();

}
