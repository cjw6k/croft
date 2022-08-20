<?php
/**
 * The ResponseInterface interface is herein defined.
 *
 * @package WebFoo\Response
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Response;

use \cjw6k\WebFoo\Service\ServiceInterface;

/**
 * The Response service interface
 */
interface ResponseInterface extends ServiceInterface
{

    /**
     * Fill in HTTP headers as needed and send the response to the client
     *
     * @return void
     */
    public function send();

}
