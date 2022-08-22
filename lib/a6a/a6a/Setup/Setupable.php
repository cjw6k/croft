<?php

namespace a6a\a6a\Setup;

/**
 * The Setupable interface
 *
 * A setupable class has an opportunity to act during first time setup.
 */
interface Setupable
{
    /**
     * Setup the extension during first-time setup
     *
     * Errors should be directly echoed to the console.
     *
     * @param Setup $setup The setup service.
     *
     * @return bool True Setup may proceed.
 * False Setup has failed.
     */
    public function setup(Setup $setup): bool;
}
