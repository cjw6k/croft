<?php

namespace a6a\a6a\Router;

use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Service\ServiceInterface;

/**
 * The Router service interface.
 */
interface RouterInterface extends ServiceInterface
{
    /**
     * Store a local reference to the active configuration and current request
     *
     * @param ConfigInterface $config The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request);

    /**
     * Map a request to a specific class and method, with path parameters
     *
     * @return mixed The class, method and parameters that are matched to the request.
     */
    public function route(): mixed;
}
