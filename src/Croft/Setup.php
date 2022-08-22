<?php

namespace Croft;

use a6a\a6a\Setup\Setup as SetupA6a;
use A6A\Aether\Aether;
use a6a\a6a\Request\Request;
use League\CLImate\CLImate;

use function file_exists;
use function parse_url;
use function substr;
use function base64_encode;
use function random_bytes;
use function password_hash;
use function yaml_emit_file;

use const PASSWORD_DEFAULT;
use const PHP_EOL;

/**
 * The Setup completes first-time configuration.
 *
 * Setup is triggered by running the setup.php script in the bin folder of the package root.
 *
 * setup.php requires USERNAME and URL parameters corresponding to the login username and the
 * IndieAuth user profile URL. A password is generated and displayed on the command line when
 * setup completes successfully.
 */
class Setup implements SetupA6a
{
    use Aether;

    /**
     * Construct the Setup
     *
     * @param Request $request The current request.
     */
    public function __construct(Request $request)
    {
        $this->setRequest($request);
    }

    /**
     * Check that the setup script was called with all prerequisites satisified
     *
     * @param mixed $argv The command lines parameters.
     *
     * @return bool True If called with all required parameters.
 * False If not called with all required parameters.
     */
    public function prerequisites(CLImate $cli): bool
    {
        if (file_exists(From::___->dir() . 'config.yml')) {
            $cli->error('croft: first time setup is already complete. should do something useful, ayuh');

            return false;
        }

        $this->ensureUrlHasPath();

        return true;
    }

    /**
     * Check that the setup script was called with all required parameters
     *
     * @param mixed $argv The command lines parameters.
     *
     * @return bool True If called with all required parameters.
 * False If not called with all required parameters.
     */
    private function hasRequiredParameters(CLImate $cli): bool
    {
        if (
            ! $cli->arguments->get('username')
            || ! $cli->arguments->get('url')
        ) {
            $cli->usage();

            return false;
        }

        return true;
    }

    /**
     * Append the root path component '/' if missing from the URL
     */
    private function ensureUrlHasPath(): void
    {
        $url_parts = parse_url($this->getUrl());

        if (isset($url_parts['path'])) {
            return;
        }

        $this->setUrl($this->getUrl() . '/');
    }

    public function configure(CLImate $cli): int
    {
        $password = substr(base64_encode(openssl_random_pseudo_bytes(12)), 0, 16);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $this->setUsername($cli->br()->yellow()->input('Choose your username:')->prompt());
        $this->setUrl($cli->yellow()->input('The URL:')->prompt());

        $config = [
            'username' => $this->getUsername(),
            'password' => $password_hash,
            'me' => $this->getUrl(),
        ];

        if (! yaml_emit_file(From::___->dir() . 'config.yml', $config)) {
            $cli->error('setup.php: An error occured writing the config to ' . From::___->dir() . 'config.yml.');

            return 1;
        }

        $cli->lightGreen()->br()->out('setup.php: Done!')->br()->out("Your temporary password is: {$password}");

        return 0;
    }
}
