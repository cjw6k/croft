<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\ContextMinkContext;

require_once __DIR__ . '/../fixtures/bootstrap.php';
require_once VENDOR_ROOT . 'phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * Defines application features from the specific context
 */
trait WebContextTrait
{
	public $db = false;
	public $hasher = false;

	/**
	 * Initializes context.
	 *
	 * Every scenario gets its own context instance.
	 * You can also pass arbitrary arguments to the
	 * context constructor through behat.yml.
	 */
	public function __construct($base_url = 'http://127.0.0.1')
	{
		$this->base_url = $base_url;
	}

	/**
	 * @BeforeScenario @user_exists
	 */
	public function makeConfigWithUsers()
	{
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file already exists');
		yaml_emit_file(PACKAGE_ROOT . 'config.yml', array('title' => 'WebFoo', 'username' => 'test', 'password' => password_hash('test', PASSWORD_DEFAULT), 'me' => 'http://localhost/'));
	}

	/**
	 * @BeforeScenario @config_indieauth_exceptions
	 */
	public function makeConfigWithUsersAndIndieAuthExceptions()
	{
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file already exists');
		yaml_emit_file(
			PACKAGE_ROOT . 'config.yml',
			array(
				'title' => 'WebFoo',
				'username' => 'test',
				'password' => password_hash('test', PASSWORD_DEFAULT), 'me' => 'http://localhost/',
				'indieauth' => array(
					'exceptions' => array(
						'client_id' => array(
							'missing_path_component' => array(
								'omnibear.com',
								'indigenous.realize.be',
								'indiebookclub.biz',
							)
						)
					)
				)
			)
		);
	}

	/**
	 * @AfterScenario
	 */
	public function resetSession()
	{
		$this->getSession()->reset();
	}

	/**
	 * @AfterScenario @user_exists
	 * @AfterScenario @config_indieauth_exceptions
	 */
	public function removeConfig()
	{
		unlink(PACKAGE_ROOT . 'config.yml');
		assertFileNotExists(PACKAGE_ROOT . 'config.yml', 'config.yml file exists');
	}

	/**
	 * @Then show me
	 */
	public function showMe()
	{
		$content = $this->getSession()->getPage()->getContent();
		$json = json_decode($content);
		if($json){
			var_dump($json);
			return;
		}
		echo $content, PHP_EOL;
	}

	/**
	 * @Given I am on a random path that doesn't exist
	 */
	public function iAmOnARandomPathThatDoesntExist()
	{
		$this->getSession()->visit(
			$this->base_url . sprintf(
				'%s/%s/%s/%s',
				$this->_randomString(),
				$this->_randomString(),
				$this->_randomString(),
				$this->_randomString()
			)
		);
	}

	private function _randomString()
	{
		return substr(md5(mt_rand()), 0, 7);
	}

	/**
	 * @Then I should see a link to :href
	 */
	public function iShouldSeeALinkTo($href)
	{
		assertNotNull($this->getSession()->getPage()->find('xpath', '//a[@href="' . $href . '"]'));
	}

	/**
	 * @Given I click on the link to :href
	 */
	public function iClickOnTheLinkTo($href)
	{
		$link = $this->getSession()->getPage()->find('xpath', '//a[@href="' . $href . '"]');
		assertNotNull($link);
		$link->click();
	}

	/**
	 * @Then the HTML should be valid
	 */
	public function theHTMLShouldBeValid()
	{
		echo 'html validation disabled; do not use external validation resource';
		return;
		$validator = new \HtmlValidator\Validator();
		$validator->setParser(HtmlValidator\Validator::PARSER_HTML5);
		$result = $validator->validateDocument($this->getSession()->getPage()->getContent());
		assertEmpty((string)$result, $result);
	}

    /**
     * @Given I log in with username :username and password :password
     */
    public function iLogInWithEmailAndPassword2($username, $password)
    {
        $this->doLogIn($username, $password);
    }

	public function doLogIn($username, $password){
		$this->getSession()->visit('/login/');
		$page = $this->getSession()->getPage();
		$page->find('named', array('field', 'username'))->setValue($username);
		$page->find('named', array('field', 'userkey'))->setValue($password);
		$page->find('css', 'button[type=submit]')->submit();
	}

    /**
     * @Then there should be an HTTP :arg1 header with value :arg2
     */
    public function thereShouldBeAnHttpHeaderWithValue($arg1, $arg2)
    {
        $headers = $this->getSession()->getResponseHeaders();
		assertArrayHasKey($arg1, $headers);

		foreach(explode(',', $headers[$arg1][0]) as $header){
			if(trim($header) == $arg2){
				$found = true;
			}
		}
		assertTrue($found);
    }

    /**
     * @Then there should not be a HTTP :arg1 header
     */
    public function thereShouldNotBeAHttpHeader($arg1)
    {
        $headers = $this->getSession()->getResponseHeaders();
		assertArrayNotHasKey($arg1, $headers);
    }

    /**
     * @Then there should be a link element with rel :arg1 and href :arg2
     */
    public function thereShouldBeALinkElementWithRelAndHref($arg1, $arg2)
    {
        $link = $this->getSession()->getPage()->find('xpath', "//link[@rel='$arg1']");
		assertNotNull($link);
		assertEquals($arg2, $link->getAttribute('href'));
    }

    /**
     * @Given I am logged in
     */
    public function iAmLoggedIn()
    {
        $this->doLogin("test", "test");
    }

    /**
     * @When I login
     */
    public function iLogin()
    {
		$page = $this->getSession()->getPage();
		$page->find('named', array('field', 'username'))->setValue("test");
		$page->find('named', array('field', 'userkey'))->setValue("test");
		$page->find('css', 'button[type=submit]')->submit();
    }

    /**
     * @Then the response should be json
     */
    public function theResponseShouldBeJson()
    {
        $headers = $this->getSession()->getResponseHeaders();
		assertArrayHasKey('Content-Type', $headers);
		assertEquals($headers['Content-Type'][0], 'application/json; charset=UTF-8');
    }

    /**
     * @Then the json should have an :arg1 parameter
     * @Then the json should have a :arg1 parameter
     */
    public function theJsonShouldHaveAnParameter($arg1)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotNull($json);
		assertObjectHasAttribute($arg1, $json);
    }

    /**
     * @Then the json should not have an :arg1 parameter
     * @Then the json should not have a :arg1 parameter
     */
    public function theJsonShouldNotHaveAnParameter($arg1)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotNull($json);
		assertObjectNotHasAttribute($arg1, $json);
    }

    /**
     * @Then the json :arg1 parameter should be :arg2
     */
    public function theJsonParameterShouldBe($arg1, $arg2)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertEquals($json->$arg1, $arg2);
    }

    /**
     * @Then the :arg1 checkbox with value :arg2 should be checked
     */
    public function theCheckboxWithValueShouldBeChecked($arg1, $arg2)
    {
        $checkbox = $this->getSession()->getPage()->find('xpath', '//input[@type="checkbox" and @name="' . $arg1 . '" and @value="' . $arg2 . '"]');
		assertTrue($checkbox->isChecked());
    }

    /**
     * @When I uncheck the :arg1 checkbox with value :arg2
     */
    public function iUncheckTheCheckboxWithValue($arg1, $arg2)
    {
        $checkbox = $this->getSession()->getPage()->find('xpath', '//input[@type="checkbox" and @name="' . $arg1 . '" and @value="' . $arg2 . '"]');
		$checkbox->uncheck();
    }

    /**
     * @Then the json :arg1 parameter should be base_url plus :arg2
     */
    public function theJsonParameterShouldBeBaseUrlPlus($arg1, $arg2)
    {
		$this->theJsonParameterShouldBe($arg1, rtrim($this->base_url, '/') . $arg2);
    }

    /**
     * @Then the json :arg1 parameter should be the empty string
     */
    public function theJsonParameterShouldBeTheEmptyString($arg1)
    {
        $this->theJsonParameterShouldBe($arg1, '');
    }

    /**
     * @Then the json :arg1 parameter should be the empty array
     */
    public function theJsonParameterShouldBeTheEmptyArray($arg1)
    {
        $this->theJsonParameterShouldBe($arg1, array());
    }

    /**
     * @Then the json :arg1 parameter should be an array with an element :arg2
     */
    public function theJsonParameterShouldBeAnArrayWithAnElement($arg1, $arg2)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotNull($json);
		assertObjectHasAttribute($arg1, $json);
		assertIsArray($json->$arg1);
		assertContains($arg2, $json->$arg1);
    }

    /**
     * @Then the json :arg1 parameter should have a nested array in :arg2 with an element :arg3
     */
    public function theJsonParameterShouldHaveANestedArrayInWithAnElement($arg1, $arg2, $arg3)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotNull($json);
		assertObjectHasAttribute($arg1, $json);
		assertIsObject($json->$arg1);
		assertObjectHasAttribute($arg2, $json->$arg1);
		assertIsArray($json->$arg1->$arg2);
		assertContains($arg3, $json->$arg1->$arg2);
    }

    /**
     * @Then the json :arg1 parameter should not have an :arg2 key
     */
    public function theJsonParameterShouldNotHaveAnKey($arg1, $arg2)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotNull($json);
		assertObjectHasAttribute($arg1, $json);
		assertObjectNotHasAttribute($arg2, $json->$arg1);
    }

}
