<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

require_once __DIR__ . '/../fixtures/bootstrap.php';
require_once VENDOR_ROOT . 'phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context.
 */
class CLIContext implements Context, SnippetAcceptingContext
{
	private $_cli_output = null;
	private $_cli_status = null;
	
	/**
	 * @BeforeScenario @make_config
	 */
	public function makeEmptyConfig()
	{
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file already exists');
		yaml_emit_file(PACKAGE_ROOT . 'config.yml', array());
	}
	
	/**
	 * @AfterScenario
	 */
	public function resetCLIOutput()
	{
		$this->_cli_output = null;
		$this->_cli_status = null;
	}
	
	/**
	 * @AfterScenario @cleanup_config
	 */
	public function removeConfig()
	{
		unlink(PACKAGE_ROOT . 'config.yml');
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file was not removed');
	}
	
	/**
	 * @Given there is no config file
	 */
	public function thereIsNoConfigFile()
	{
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file exists');
	}	
	
	/**
	 * @Given there is a config file
	 */
	public function thereIsAConfigFile()
	{
		assertFileExists(PACKAGE_ROOT . 'config.yml', 'config.yml file does not exist');
	}
	
	/**
	 * @When I run the setup script with no arguments
	 */
	public function iRunTheSetupScriptWithNoArguments()
	{
		exec('php ' . PACKAGE_ROOT . 'bin/setup.php', $this->_cli_output, $this->_cli_status);
	}	
	
	/**
	 * @When I run the setup script with arguments :arg1
	 */
	public function iRunTheSetupScriptWithArguments($arg1)
	{
		exec('php ' . PACKAGE_ROOT . 'bin/setup.php ' . $arg1, $this->_cli_output, $this->_cli_status);
	}
	
    /**
     * @Then I should see :arg1
     */
    public function iShouldSee($arg1)
    {
		assertNotEmpty($this->_cli_output);
        assertStringContainsString($arg1, implode(PHP_EOL, $this->_cli_output));
    }
	
    /**
     * @Then the exit status should be :arg1
     */
    public function theExitStatusShouldBe($arg1)
    {
        assertEquals($arg1, $this->_cli_status);
    }	

    /**
     * @Then the config file should have key :arg1 with value :arg2
     */
    public function theConfigFileShouldHaveKeyWithValue($arg1, $arg2)
    {
        $config = yaml_parse_file(PACKAGE_ROOT . 'config.yml');
		assertArrayHasKey($arg1, $config);
		assertEquals($arg2, $config[$arg1]);
    }

    /**
     * @Then I should see a temporary password
     */
    public function iShouldSeeATemporaryPassword()
    {
        assertStringContainsString("Your temporary password is: ", implode(PHP_EOL, $this->_cli_output));
		
    }
	
    /**
     * @Then the config file should contain the password hash
     */
    public function theConfigFileShouldContainThePasswordHash()
    {
        assertEquals(1, preg_match('/^Your temporary password is: (.*)/m', implode(PHP_EOL, $this->_cli_output), $matches));
        $config = yaml_parse_file(PACKAGE_ROOT . 'config.yml');
		assertArrayHasKey('password', $config);
		assertTrue(password_verify($matches[1], $config['password']));		
    }
	
}
