<?php
/**
 * The HTTPLinkable interface is herein defined.
 *
 * @package WebFoo\Response
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Response;

/**
 * The HTTPLinkable interface
 *
 * An HTTPLinkable class has an opportunity to add HTTP Link headers.
 */
interface HTTPLinkable
{

    /**
     * Provide HTTP link header configuration to the Response\HTTP
     *
     * @return mixed[] An array of HTTP link headers.
     */
    public function getHTTPLinks();

}
