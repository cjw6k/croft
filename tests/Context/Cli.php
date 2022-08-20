<?php

namespace Tests\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Croft\From;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;
use function yaml_emit_file;
use function file_exists;
use function unlink;
use function exec;
use function implode;
use function yaml_parse_file;
use function preg_match;
use function password_verify;

use const PHP_EOL;

/**
 * Defines application features from the specific context.
 */
class Cli implements Context, SnippetAcceptingContext
{
    /** @var list<string>|null */
    private ?array $cliOutput = null;
    private ?int $cliStatus = null;

    /** @BeforeScenario @make_config */
    public function makeEmptyConfig(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file already exists');
        yaml_emit_file(From::___->dir() . 'config.yml', []);
    }

    /** @AfterScenario */
    public function resetCLIOutput(): void
    {
        $this->cliOutput = null;
        $this->cliStatus = null;
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
        exec('php ' . From::___->dir() . 'bin/setup.php', $this->cliOutput, $this->cliStatus);
    }

    /** @When I run the setup script with arguments :arg1 */
    public function iRunTheSetupScriptWithArguments(string $arg1): void
    {
        exec('php ' . From::___->dir() . 'bin/setup.php ' . $arg1, $this->cliOutput, $this->cliStatus);
    }

    /** @When I run the setup script with user :arg1 and URL :arg2 */
    public function iRunTheSetupScriptWithUserAndUrl(string $arg1, string $arg2): void
    {
        exec('php ' . From::___->dir() . 'bin/setup.php ' . $arg1 . ' ' . $arg2, $this->cliOutput, $this->cliStatus);
    }

    /** @Then I should see :arg1 */
    public function iShouldSee(string $arg1): void
    {
        assertNotEmpty($this->cliOutput);
        assertStringContainsString($arg1, implode(PHP_EOL, $this->cliOutput));
    }

    /** @Then the exit status should be :arg1 */
    public function theExitStatusShouldBe(string $arg1): void
    {
        assertEquals($arg1, $this->cliStatus);
    }

    /** @Then the config file should have key :arg1 with value :arg2 */
    public function theConfigFileShouldHaveKeyWithValue(string $arg1, string $arg2): void
    {
        $config = yaml_parse_file(From::___->dir() . 'config.yml');
        assertArrayHasKey($arg1, $config);
        assertEquals($arg2, $config[$arg1]);
    }

    /** @Then I should see a temporary password */
    public function iShouldSeeATemporaryPassword(): void
    {
        assertStringContainsString("Your temporary password is: ", implode(PHP_EOL, $this->cliOutput));
    }

    /** @Then the config file should contain the password hash */
    public function theConfigFileShouldContainThePasswordHash(): void
    {
        assertEquals(
            1,
            preg_match('/^Your temporary password is: (.*)/m', implode(PHP_EOL, $this->cliOutput), $matches)
        );
        $config = yaml_parse_file(From::___->dir() . 'config.yml');
        assertArrayHasKey('password', $config);
        assertTrue(password_verify($matches[1], $config['password']));
    }
}
