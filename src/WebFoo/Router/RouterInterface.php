<?php
/**
 * The RouterInterface interface is herein defined.
 *
 * @package WebFoo\Router
 * @author  cjw6k
 * @link    https://cj.w6k.ca/
 */

namespace cjw6k\WebFoo\Router;

use \cjw6k\WebFoo\Config\ConfigInterface;
use \cjw6k\WebFoo\Request\RequestInterface;
use \cjw6k\WebFoo\Service\ServiceInterface;

/**
 * The Router service interface.
 */
interface RouterInterface extends ServiceInterface
{

    /**
     * Store a local reference to the active configuration and current request
     *
     * @param ConfigInterface  $config  The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request);

    /**
     * Map a request to a specific class and method, with path parameters
     *
     * @return mixed The class, method and parameters that are matched to the request.
     */
    public function route();

}
