<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class PublicContext extends MinkContext implements Context, SnippetAcceptingContext
{
	use WebContextTrait;

	private $_auth_url;

    /**
     * @Given I start an IndieAuth authorization flow
     */
    public function iStartAnIndieauthAuthorizationFlow()
    {
		IndieAuth\Client::$clientID = 'https://example.com';
		IndieAuth\Client::$redirectURL = 'https://example.com/redirect.php';
    }

    /**
     * @When the client tries to discover the authorization endpoint
     */
    public function theClientTriesToDiscoverTheAuthorizationEndpoint()
    {
        $this->_auth_url = IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url);
		assertNotEmpty($this->_auth_url);
    }

    /**
     * @Then the authorization_endpoint is base_url plus :arg1
     */
    public function theAuthorizationEndpointIsBaseURLPlus($arg1)
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_auth_url);
    }

    /**
     * @Given I receive an authentication request
     */
    public function iReceiveAnAuthenticationRequest()
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'https://example.com/',
			'https://example.com/',
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given I am not logged in
     */
    public function iAmNotLoggedIn()
    {
		$this->getSession()->visit('/');
        $this->getSession()->setCookie('webfoo', null);
    }

    /**
     * @Given the authentication request has no client_id parameter
     */
    public function theAuthenticationRequestHasNoClientIdParameter()
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'https://example.com/',
			'',
			'test',
			'id',
			'secret'
		);
		$authorization_url = str_replace('client_id=&', '&', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has client_id :arg1
     */
    public function theAuthenticationRequestHasClientId($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'https://example.com/',
			$arg1,
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has no redirect_uri parameter
     */
    public function theAuthenticationRequestHasNoRedirectUriParameter()
    {
 		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'',
			'https://example.com/',
			'test',
			'id',
			'secret'
		);
		$authorization_url = str_replace('redirect_uri=&', '&', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has redirect_uri parameter :arg1
     */
    public function theAuthenticationRequestHasRedirectUriParameter($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			$arg1,
			'https://example.com/',
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has redirect_uri parameter :arg1 with client_id :arg2
     */
    public function theAuthenticationRequestHasRedirectUriParameterWithClientId($arg1, $arg2)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			$arg1,
			$arg2,
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has no state parameter
     */
    public function theAuthenticationRequestHasNoStateParameter()
    {
 		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'https://example.com/',
			'https://example.com/',
			'',
			'id',
			'secret'
		);
		$authorization_url = str_replace('state=&', '&', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given I receive an authentication request from :arg1
     */
    public function iReceiveAnAuthenticationRequestFrom($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			$arg1,
			$arg1,
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

}
