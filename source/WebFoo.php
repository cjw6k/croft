<?php
/**
 * The WebFoo class is herein defined.
 *
 * @package	webfoo
 * @author	cjw6k.ca
 * @link	https://cj.w6k.ca/
 */

namespace cjw6k;

/**
 * The WebFoo Class is the main slingin' thinger.
 */
class WebFoo
{

	/**
	 * Sling some web stuff with this thinger.
	 *
	 * @return void
	 */
	public function sling()
	{
		if('/' != filter_input(INPUT_SERVER, 'REQUEST_URI')){
			http_response_code(404);
		}
		echo '<!DOCTYPE html><html lang="en-CA"><head><meta charset="utf-8"><title>web stuff</title></head><body><a href="/">home</a></body></html>';
	}

}
