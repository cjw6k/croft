<?php
/**
 * The Setupable interface is herein defined.
 *
 * @package	WebFoo\Setup
 * @author	cjw6k
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Setup;

/**
 * The Setupable interface
 *
 * A setupable class has an opportunity to act during first time setup.
 */
interface Setupable
{

	/**
	 * Setup the extension during first-time setup
	 *
	 * Errors should be directly echoed to the console.
	 *
	 * @param Setup $setup The setup service.
	 *
	 * @return boolean True  Setup may proceed.
	 *                 False Setup has failed.
	 */
	public function setup(Setup $setup);

}
