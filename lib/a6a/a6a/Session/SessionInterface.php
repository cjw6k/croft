<?php
/**
 * The SessionInterface interface is herein defined.
 *
 * @package WebFoo\Session
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Session;

use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;

/**
 * The Session service interface.
 */
interface SessionInterface extends ServiceInterface
{

    /**
     * Construct the Session
     *
     * @param ConfigInterface  $config  The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request);

    /**
     * Setup the session and start the session handler
     *
     * @return void
     */
    public function start();

}
