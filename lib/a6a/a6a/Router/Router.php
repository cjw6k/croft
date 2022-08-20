<?php

namespace a6a\a6a\Router;

use a6a\a6a\Config\Config;
use a6a\a6a\Request\Request;
use a6a\a6a\Service\Service;

/**
 * The Router service interface.
 */
interface Router extends Service
{
    /**
     * Store a local reference to the active configuration and current request
     *
     * @param Config $config The active configuration.
     * @param Request $request The current request.
     */
    public function __construct(Config $config, Request $request);

    /**
     * Map a request to a specific class and method, with path parameters
     *
     * @return mixed The class, method and parameters that are matched to the request.
     */
    public function route(): mixed;
}
