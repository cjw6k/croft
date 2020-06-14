<?php
/**
 * The Micropub class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo;

/**
 * The Micropub class implements a Micropub server
 */
class Micropub
{

	use Aether;

	/**
	 * Send the Micropub HTTP link-rel header
	 */
	public function __construct()
	{
		$this->mergeHTTPLinks('</micropub/>; rel="micropub"');
	}

}
