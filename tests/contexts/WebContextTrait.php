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
		yaml_emit_file(PACKAGE_ROOT . 'config.yml', array('username' => 'test', 'password' => password_hash('test', PASSWORD_DEFAULT)));
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
		$html = $this->getSession()->getPage()->getHtml();
		echo $html;
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
		assertEquals($headers[$arg1][0], $arg2);
    }

    /**
     * @Then there should be a link element with rel :arg1 and href :arg2
     */
    public function thereShouldBeALinkElementWithRelAndHref($arg1, $arg2)
    {
        $auth_endpoint_link = $this->getSession()->getPage()->find('xpath', "//link[@rel='authorization_endpoint']");
		assertNotNull($auth_endpoint_link);
		assertEquals('/auth/', $auth_endpoint_link->getAttribute('href'));
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

}
