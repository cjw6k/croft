<?php

namespace a6a\a6a\Session;

use a6a\a6a\Config\Config;
use a6a\a6a\Request\Request;
use a6a\a6a\Service\Service;

/**
 * The Session service interface.
 */
interface Session extends Service
{
    /**
     * Construct the Session
     *
     * @param Config $config The active configuration.
     * @param Request $request The current request.
     */
    public function __construct(Config $config, Request $request);

    /**
     * Setup the session and start the session handler
     */
    public function start(): void;
}
