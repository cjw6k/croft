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

    /**
     * @Given I have not approved an authentication request
     */
    public function iHaveNotApprovedAnAuthenticationRequest()
    {
        $files = glob(VAR_ROOT . 'indieauth/auth-*');
		foreach($files as $file){
			if(is_file($file)){
				unlink($file);
			}
		}
    }

    /**
     * @When I receive an authorization verification request
     */
    public function iReceiveAnAuthorizationVerificationRequest()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/auth/',
			array(
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => isset($this->_indieauth_code) ? $this->_indieauth_code : 'test',
			)
		);
    }

    /**
     * @Given I have approved an authentication request
     */
    public function iHaveApprovedAnAuthenticationRequest()
    {
		$this->iAmLoggedIn();
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			'id',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
		$this->getSession()->getPage()->findButton('Continue')->press();
		$redirect_url = parse_url($this->getSession()->getCurrentUrl());
		parse_str($redirect_url['query'], $params);
		$this->_indieauth_code = $params['code'];
		$this->resetSession();
    }

    /**
     * @When the authorization verification request has no code parameter
     */
    public function theAuthorizationVerificationRequestHasNoCodeParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/auth/',
			array(
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				//'code' => 'test',
			)
		);
    }

    /**
     * @When the authorization verification request has no client_id parameter
     */
    public function theAuthorizationVerificationRequestHasNoClientIdParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/auth/',
			array(
				// 'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
			)
		);
    }

    /**
     * @When the authorization verification request has no redirect_uri parameter
     */
    public function theAuthorizationVerificationRequestHasNoRedirectUriParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/auth/',
			array(
				'client_id' => 'http://localhost/fake/',
				// 'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
			)
		);
    }

    /**
     * @Then the the authorization code should be marked as having been used
     */
    public function theTheAuthorizationCodeShouldBeMarkedAsHavingBeenUsed()
    {
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertNotFalse($approval);
		assertEquals(1, $approval['used']);
    }

    /**
     * @Then the the authorization code should be marked as having been used twice
     */
    public function theTheAuthorizationCodeShouldBeMarkedAsHavingBeenUsedTwice()
    {
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertNotFalse($approval);
		assertEquals(2, $approval['used']);
    }

    /**
     * @Given I have approved an authentication request more than ten minutes ago
     */
    public function iHaveApprovedAnAuthenticationRequestMoreThanTenMinutesAgo()
    {
		$client_id = $redirect_uri = 'http://localhost/fake/';
		$this->_indieauth_code = $code = 'expired';
		$approval = array(
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'code' => password_hash($code, PASSWORD_DEFAULT),
			'expires' => now() - 601,
			'used' => 0,
		);

		$filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
		assertNotFalse(yaml_emit_file(VAR_ROOT . 'indieauth/auth-' . $filename, $approval));
    }

    /**
     * @Then the the authorization code should not be marked as having been used
     */
    public function theTheAuthorizationCodeShouldNotBeMarkedAsHavingBeenUsed()
    {
		$client_id = $redirect_uri = 'http://localhost/fake/';
		$filename = hash('sha1', "[$client_id][$redirect_uri][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertEquals(0, $approval['used']);
    }


}
