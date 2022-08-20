<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Croft\From;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;

require_once From::TESTS___FIXTURES->dir() . 'bootstrap.php';
require_once From::VENDOR->dir() . 'phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class CLIContext implements Context, SnippetAcceptingContext
{
    private $_cli_output = null;
    private $_cli_status = null;

    /** @BeforeScenario @make_config */
    public function makeEmptyConfig(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file already exists');
        yaml_emit_file(From::___->dir() . 'config.yml', []);
    }

    /** @AfterScenario */
    public function resetCLIOutput(): void
    {
        $this->_cli_output = null;
        $this->_cli_status = null;

    }

    /** @AfterScenario @cleanup_config */
    public function removeConfig(): void
    {
        if (file_exists(From::___->dir() . 'config.yml')) {
            unlink(From::___->dir() . 'config.yml');
        }

        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file was not removed');
    }

    /** @Given there is no config file */
    public function thereIsNoConfigFile(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file exists');
    }

    /** @Given there is a config file */
    public function thereIsAConfigFile(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file does not exist');
    }

    /** @When I run the setup script with no arguments */
    public function iRunTheSetupScriptWithNoArguments(): void
    {
        exec('php ' . From::___->dir() . 'bin/setup.php', $this->_cli_output, $this->_cli_status);
    }

    /** @When I run the setup script with arguments :arg1 */
    public function iRunTheSetupScriptWithArguments($arg1): void
    {
        exec('php ' . From::___->dir() . 'bin/setup.php ' . $arg1, $this->_cli_output, $this->_cli_status);
    }

    /** @When I run the setup script with user :arg1 and URL :arg2 */
    public function iRunTheSetupScriptWithUserAndUrl($arg1, $arg2): void
    {
        exec('php ' . From::___->dir() . 'bin/setup.php ' . $arg1 . ' ' . $arg2, $this->_cli_output, $this->_cli_status);
    }

    /** @Then I should see :arg1 */
    public function iShouldSee($arg1): void
    {
        assertNotEmpty($this->_cli_output);
        assertStringContainsString($arg1, implode(PHP_EOL, $this->_cli_output));
    }

    /** @Then the exit status should be :arg1 */
    public function theExitStatusShouldBe($arg1): void
    {
        assertEquals($arg1, $this->_cli_status);
    }

    /** @Then the config file should have key :arg1 with value :arg2 */
    public function theConfigFileShouldHaveKeyWithValue($arg1, $arg2): void
    {
        $config = yaml_parse_file(From::___->dir() . 'config.yml');
        assertArrayHasKey($arg1, $config);
        assertEquals($arg2, $config[$arg1]);
    }

    /** @Then I should see a temporary password */
    public function iShouldSeeATemporaryPassword(): void
    {
        assertStringContainsString("Your temporary password is: ", implode(PHP_EOL, $this->_cli_output));
    }

    /** @Then the config file should contain the password hash */
    public function theConfigFileShouldContainThePasswordHash(): void
    {
        assertEquals(1, preg_match('/^Your temporary password is: (.*)/m', implode(PHP_EOL, $this->_cli_output), $matches));
        $config = yaml_parse_file(From::___->dir() . 'config.yml');
        assertArrayHasKey('password', $config);
        assertTrue(password_verify($matches[1], $config['password']));
    }
}
