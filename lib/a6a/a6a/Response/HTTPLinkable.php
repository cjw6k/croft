<?php

namespace a6a\a6a\Response;

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
     * @return array<mixed> An array of HTTP link headers.
     */
    public function getHTTPLinks(): array;
}
