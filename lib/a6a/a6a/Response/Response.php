<?php

namespace a6a\a6a\Response;

use a6a\a6a\Service\Service;

/**
 * The Response service interface
 */
interface Response extends Service
{
    /**
     * Fill in HTTP headers as needed and send the response to the client
     */
    public function send(): void;
}
