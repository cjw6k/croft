<?php

namespace a6a\a6a\Session;

use a6a\a6a\Config\ConfigInterface;
use a6a\a6a\Request\RequestInterface;
use a6a\a6a\Service\ServiceInterface;

/**
 * The Session service interface.
 */
interface SessionInterface extends ServiceInterface
{
    /**
     * Construct the Session
     *
     * @param ConfigInterface $config The active configuration.
     * @param RequestInterface $request The current request.
     */
    public function __construct(ConfigInterface $config, RequestInterface $request);

    /**
     * Setup the session and start the session handler
     */
    public function start(): void;
}
