<?php

namespace a6a\a6a\Setup;

use A6A\Aether\Aether;
use League\CLImate\CLImate;

/**
 * @see Aether
 * @todo an interface consumer relies on the getUrl method, but it is provided via magic __call handling, boo
 * @method string getUrl()
 */
interface SetupInterface
{
    /**
     * Configure WebFoo using parameters provided on the command line
     *
     * @param CLImate $cli
     *
     * @return int The return code.
     */
    public function configure(CLImate $cli): int;
}
