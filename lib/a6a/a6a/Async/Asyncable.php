<?php

namespace a6a\a6a\Async;

/**
 * The Asyncable interface
 *
 * An asyncable class has an opportunity to act during async requests, after the HTTP response has
 * been sent to the client and the connection has been closed.
 */
interface Asyncable
{
    /**
     * Do some work after the current HTTP response has been sent and the connection closed.
     *
     * Async operations will run at most, once per thirty (30) seconds. If an async process is
     * already running, a new one will not be started.
     *
     * It is better to do big things in small chunks, than all in one long-running async task.
     */
    public function async(): void;
}
