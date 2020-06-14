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
     * @When the client tries to discover the token endpoint
     */
    public function theClientTriesToDiscoverTheTokenEndpoint()
    {
        $this->_token_url = IndieAuth\Client::discoverTokenEndpoint($this->base_url);
		assertNotEmpty($this->_token_url);
    }

    /**
     * @Then the token_endpoint is base_url plus :arg1
     */
    public function theTokenEndpointIsBaseUrlPlus($arg1)
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_token_url);
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
			'',
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
     * @Given the authentication request has no me parameter
     */
    public function theAuthenticationRequestHasNoMeParameter()
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			'',
			'https://example.com/',
			'https://example.com/',
			'test',
			'',
			'secret'
		);
		$authorization_url = str_replace('me=&', '&', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has me parameter :arg1
     */
    public function theAuthenticationRequestHasMeParameter($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$arg1,
			'https://example.com/',
			'https://example.com/',
			'test',
			'',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
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
			'',
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
			'',
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
			'',
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
			'',
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
			'',
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
			'',
			'secret'
		);
		$authorization_url = str_replace('state=&', '&', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authentication request has scope parameter :arg1
     */
    public function theAuthenticationRequestHasScopeParameter($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'https://example.com/',
			'https://example.com/',
			'test',
			$arg1,
			'secret'
		);
		$authorization_url = str_replace('response_type=code', 'response_type=id', $authorization_url);
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
			'',
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
			'',
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
     * @Then the authorization code should be marked as having been used
     */
    public function theAuthorizationCodeShouldBeMarkedAsHavingBeenUsed()
    {
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertNotFalse($approval);
		assertEquals(1, $approval['used']);
    }

    /**
     * @Then the authorization code should be marked as having been used twice
     */
    public function theAuthorizationCodeShouldBeMarkedAsHavingBeenUsedTwice()
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
     * @Then the authorization code should not be marked as having been used
     */
    public function theAuthorizationCodeShouldNotBeMarkedAsHavingBeenUsed()
    {
		$client_id = $redirect_uri = 'http://localhost/fake/';
		$filename = hash('sha1', "[$client_id][$redirect_uri][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertEquals(0, $approval['used']);
    }

    /**
     * @Given I receive an authorization request
     */
    public function iReceiveAnAuthorizationRequest()
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			'identity',
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given the authorization request is missing the scope parameter
     */
    public function theAuthorizationRequestIsMissingTheScopeParameter()
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			'',
			'secret'
		);
		$authorization_url = str_replace('scope=&', '&', $authorization_url);
		$authorization_url = str_replace('response_type=id', 'response_type=code', $authorization_url);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Given I receive an authorization request with scope parameter :arg1
     */
    public function iReceiveAnAuthorizationRequestWithScopeParameter($arg1)
    {
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			$arg1,
			'secret'
		);
		$this->getSession()->visit($authorization_url);
    }

    /**
     * @Then the authorization record should have scope :arg1
     */
    public function theAuthorizationRecordShouldHaveScope($arg1)
    {
		$redirect_url = parse_url($this->getSession()->getCurrentUrl());
		parse_str($redirect_url['query'], $params);
		$this->_indieauth_code = $params['code'];
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertNotFalse($approval);
		assertContains($arg1, $approval['scopes']);
    }

    /**
     * @Then the authorization record should not have scope :arg1
     */
    public function theAuthorizationRecordShouldNotHaveScope($arg1)
    {
		$redirect_url = parse_url($this->getSession()->getCurrentUrl());
		parse_str($redirect_url['query'], $params);
		$this->_indieauth_code = $params['code'];
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
		assertNotFalse($approval);
		assertNotContains($arg1, $approval['scopes']);
    }

    /**
     * @Given I have approved an authorization request with scope parameter :arg1
     */
    public function iHaveApprovedAnAuthorizationRequestWithScopeParameter($arg1)
    {
		$this->iAmLoggedIn();
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			$arg1,
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
     * @When the client requests a token
     */
    public function theClientRequestsAToken()
    {
		$token_endpoint = IndieAuth\Client::discoverTokenEndpoint($this->base_url);
        $token = IndieAuth\Client::getAccessToken(
			$token_endpoint,
			$this->_indieauth_code,
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'secret'
		);
    }

    /**
     * @Then the json :arg1 parameter should match the recorded access token
     */
    public function theJsonParameterShouldMatchTheRecordedAccessToken($arg1)
    {
		$content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
		assertNotFalse($json);
		assertObjectHasAttribute($arg1, $json);
		$response_token = $json->$arg1;
		assertFileExists(VAR_ROOT . 'indieauth/token-' . $response_token);
		$auth = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $response_token);
		assertFileExists(VAR_ROOT . 'indieauth/auth-' . $auth['auth']);
		$approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $auth['auth']);
		assertTrue(password_verify($this->_indieauth_code, $approval['code']));
    }

    /**
     * @Given I receive a token request with no grant_type parameter
     */
    public function iReceiveATokenRequestWithNoGrantTypeParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				// 'grant_type' => 'authorization_code',
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
				'me' => 'http://localhost/'
			)
		);
    }

    /**
     * @Given I receive a token request with grant_type parameter :arg1
     */
    public function iReceiveATokenRequestWithGrantTypeParameter($arg1)
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => $arg1,
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
				'me' => 'http://localhost/'
			)
		);
    }


    /**
     * @Given I receive a token request with no client_id parameter
     */
    public function iReceiveATokenRequestWithNoClientIdParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => 'authorization_code',
				//'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
				'me' => 'http://localhost/'
			)
		);
    }

    /**
     * @Given I receive a token request with no redirect_uri parameter
     */
    public function iReceiveATokenRequestWithNoRedirectUriParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => 'authorization_code',
				'client_id' => 'http://localhost/fake/',
				//'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
				'me' => 'http://localhost/'
			)
		);
    }

    /**
     * @Given I receive a token request with no code parameter
     */
    public function iReceiveATokenRequestWithNoCodeParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => 'authorization_code',
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				//'code' => 'test',
				'me' => 'http://localhost/'
			)
		);
    }

    /**
     * @Given I receive a token request with no me parameter
     */
    public function iReceiveATokenRequestWithNoMeParameter()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => 'authorization_code',
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => 'test',
				//'me' => 'http://localhost/'
			)
		);
    }

    /**
     * @Given I have not approved an authorization request
     */
    public function iHaveNotApprovedAnAuthorizationRequest()
    {
        $this->iHaveNotApprovedAnAuthenticationRequest();
    }

    /**
     * @When I receive a token request
     */
    public function iReceiveATokenRequest()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'grant_type' => 'authorization_code',
				'client_id' => 'http://localhost/fake/',
				'redirect_uri' => 'http://localhost/fake/',
				'code' => isset($this->_indieauth_code) ? $this->_indieauth_code : 'test',
				'me' => 'http://localhost/'
			)
		);
		$response = json_decode($this->getSession()->getPage()->getContent());
		assertNotFalse($response);
		if(isset($response->access_token)){
			$this->_indieauth_token = $response->access_token;
		}
    }

    /**
     * @Given I have approved an authorization request more than ten minutes ago
     */
    public function iHaveApprovedAnAuthorizationRequestMoreThanTenMinutesAgo()
    {
		$this->iHaveApprovedAnAuthenticationRequestMoreThanTenMinutesAgo();
    }

    /**
     * @Given I have approved an authorization request
     */
    public function iHaveApprovedAnAuthorizationRequest()
    {
		$this->iAmLoggedIn();
		$authorization_url = IndieAuth\Client::buildAuthorizationURL(
			IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
			$this->base_url,
			'http://localhost/fake/',
			'http://localhost/fake/',
			'test',
			'create update delete',
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
     * @Given no tokens have been issued
     */
    public function noTokensHaveBeenIssued()
    {
        $files = glob(VAR_ROOT . 'indieauth/token-*');
		foreach($files as $file){
			if(is_file($file)){
				unlink($file);
			}
		}
    }

    /**
     * @When I receive a token revocation request
     */
    public function iReceiveATokenRevocationRequest()
    {
		$this->getSession()->getDriver()->getClient()->request(
			'POST',
			'/token/',
			array(
				'action' => 'revoke',
				'token' => isset($this->_indieauth_token) ? $this->_indieauth_token : 'test',
			)
		);
    }

    /**
     * @Then the response should be empty
     */
    public function theResponseShouldBeEmpty()
    {
        assertEmpty($this->getSession()->getPage()->getContent());
    }

    /**
     * @Given an access token has been issued
     */
    public function anAccessTokenHasBeenIssued()
    {
        $this->iReceiveATokenRequest();
		assertFileExists(VAR_ROOT . 'indieauth/token-' . $this->_indieauth_token);
    }

    /**
     * @Then the token should be marked as revoked
     */
    public function theTokenShouldBeMarkedAsRevoked()
    {
        $token = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $this->_indieauth_token);
		assertNotFalse($token);
		assertArrayHasKey('revoked', $token);
    }

    /**
     * @Given I use the indieauth-client library
     */
    public function iUseTheIndieauthClientLibrary()
    {
    }

    /**
     * @When the client tries to discover the micropub endpoint
     */
    public function theClientTriesToDiscoverTheMicropubEndpoint()
    {
        $this->_micropub_endpoint = IndieAuth\Client::discoverMicropubEndpoint($this->base_url);
		assertNotEmpty($this->_micropub_endpoint);
    }

    /**
     * @Then the micropub endpoint is base_url plus :arg1
     */
    public function theMicropubEndpointIsBaseUrlPlus($arg1)
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_micropub_endpoint);
    }
}