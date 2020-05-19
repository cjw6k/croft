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
	 * @AfterScenario
	 */
	public function resetSession()
	{
		$this->getSession()->reset();
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
}
