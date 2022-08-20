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

    /** @BeforeScenario @micropub_authorized */
    public function micropubAuthorizedBefore(): void
    {
        $this->makeConfigWithUsers();
        $this->iHaveApprovedAnAuthorizationRequest();
        $this->iReceiveATokenRequest();
    }

    /** @AfterScenario @micropub_authorized */
    public function micropubAuthorizedAfter(): void
    {
        $this->removeConfig();
    }

    /** @Given I start an IndieAuth authorization flow */
    public function iStartAnIndieauthAuthorizationFlow(): void
    {
        IndieAuth\Client::$clientID = 'https://example.com';
        IndieAuth\Client::$redirectURL = 'https://example.com/redirect.php';
    }

    /** @When the client tries to discover the authorization endpoint */
    public function theClientTriesToDiscoverTheAuthorizationEndpoint(): void
    {
        $this->_auth_url = IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url);
        assertNotEmpty($this->_auth_url);
    }

    /** @Then the authorization_endpoint is base_url plus :arg1 */
    public function theAuthorizationEndpointIsBaseURLPlus($arg1): void
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_auth_url);
    }

    /** @When the client tries to discover the token endpoint */
    public function theClientTriesToDiscoverTheTokenEndpoint(): void
    {
        $this->_token_url = IndieAuth\Client::discoverTokenEndpoint($this->base_url);
        assertNotEmpty($this->_token_url);
    }

    /** @Then the token_endpoint is base_url plus :arg1 */
    public function theTokenEndpointIsBaseUrlPlus($arg1): void
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_token_url);
    }

    /** @Given I receive an authentication request */
    public function iReceiveAnAuthenticationRequest(): void
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

    /** @Given I am not logged in */
    public function iAmNotLoggedIn(): void
    {
        $this->getSession()->visit('/');
        $this->getSession()->setCookie('webfoo', null);
    }

    /** @Given the authentication request has no me parameter */
    public function theAuthenticationRequestHasNoMeParameter(): void
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

    /** @Given the authentication request has me parameter :arg1 */
    public function theAuthenticationRequestHasMeParameter($arg1): void
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

    /** @Given the authentication request has no client_id parameter */
    public function theAuthenticationRequestHasNoClientIdParameter(): void
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

    /** @Given the authentication request has client_id :arg1 */
    public function theAuthenticationRequestHasClientId($arg1): void
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

    /** @Given the authentication request has no redirect_uri parameter */
    public function theAuthenticationRequestHasNoRedirectUriParameter(): void
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

    /** @Given the authentication request has redirect_uri parameter :arg1 */
    public function theAuthenticationRequestHasRedirectUriParameter($arg1): void
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

    /** @Given the authentication request has redirect_uri parameter :arg1 with client_id :arg2 */
    public function theAuthenticationRequestHasRedirectUriParameterWithClientId($arg1, $arg2): void
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

    /** @Given the authentication request has no state parameter */
    public function theAuthenticationRequestHasNoStateParameter(): void
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

    /** @Given the authentication request has scope parameter :arg1 */
    public function theAuthenticationRequestHasScopeParameter($arg1): void
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

    /** @Given I receive an authentication request from :arg1 */
    public function iReceiveAnAuthenticationRequestFrom($arg1): void
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

    /** @Given I have not approved an authentication request */
    public function iHaveNotApprovedAnAuthenticationRequest(): void
    {
        $files = glob(VAR_ROOT . 'indieauth/auth-*');

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            unlink($file);
        }
    }

    /** @When I receive an authorization verification request */
    public function iReceiveAnAuthorizationVerificationRequest(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/auth/',
            [
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => $this->_indieauth_code ?? 'test',
            ]
        );
    }

    /** @Given I have approved an authentication request */
    public function iHaveApprovedAnAuthenticationRequest(): void
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

    /** @When the authorization verification request has no code parameter */
    public function theAuthorizationVerificationRequestHasNoCodeParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/auth/',
            [
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
            //'code' => 'test',
            ]
        );
    }

    /** @When the authorization verification request has no client_id parameter */
    public function theAuthorizationVerificationRequestHasNoClientIdParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/auth/',
            [
            // 'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
            ]
        );
    }

    /** @When the authorization verification request has no redirect_uri parameter */
    public function theAuthorizationVerificationRequestHasNoRedirectUriParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/auth/',
            [
                'client_id' => 'http://localhost/fake/',
            // 'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
            ]
        );
    }

    /** @Then the authorization code should be marked as having been used */
    public function theAuthorizationCodeShouldBeMarkedAsHavingBeenUsed(): void
    {
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
        assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
        $approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
        assertNotFalse($approval);
        assertEquals(1, $approval['used']);
    }

    /** @Then the authorization code should be marked as having been used twice */
    public function theAuthorizationCodeShouldBeMarkedAsHavingBeenUsedTwice(): void
    {
        $filename = hash('sha1', "[http://localhost/fake/][http://localhost/fake/][{$this->_indieauth_code}]");
        assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
        $approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
        assertNotFalse($approval);
        assertEquals(2, $approval['used']);
    }

    /** @Given I have approved an authentication request more than ten minutes ago */
    public function iHaveApprovedAnAuthenticationRequestMoreThanTenMinutesAgo(): void
    {
        $client_id = $redirect_uri = 'http://localhost/fake/';
        $this->_indieauth_code = $code = 'expired';
        $approval = [
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'expires' => time() - 601,
            'used' => 0,
        ];

        $filename = hash('sha1', "[$client_id][$redirect_uri][$code]");
        assertNotFalse(yaml_emit_file(VAR_ROOT . 'indieauth/auth-' . $filename, $approval));
    }

    /** @Then the authorization code should not be marked as having been used */
    public function theAuthorizationCodeShouldNotBeMarkedAsHavingBeenUsed(): void
    {
        $client_id = $redirect_uri = 'http://localhost/fake/';
        $filename = hash('sha1', "[$client_id][$redirect_uri][{$this->_indieauth_code}]");
        assertFileExists(VAR_ROOT . 'indieauth/auth-' . $filename);
        $approval = yaml_parse_file(VAR_ROOT . 'indieauth/auth-' . $filename);
        assertEquals(0, $approval['used']);
    }

    /** @Given I receive an authorization request */
    public function iReceiveAnAuthorizationRequest(): void
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

    /** @Given I receive an authorization request with client_id :arg1 and redirect_uri :arg2 */
    public function iReceiveAnAuthorizationRequestWithClientIdAndRedirectUri($arg1, $arg2): void
    {
        $authorization_url = IndieAuth\Client::buildAuthorizationURL(
            IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
            $this->base_url,
            $arg2,
            $arg1,
            'test',
            'identity',
            'secret'
        );
        $this->getSession()->visit($authorization_url);
    }

    /** @Given the authorization request is missing the scope parameter */
    public function theAuthorizationRequestIsMissingTheScopeParameter(): void
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

    /** @Given I receive an authorization request with scope parameter :arg1 */
    public function iReceiveAnAuthorizationRequestWithScopeParameter($arg1): void
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

    /** @Then the authorization record should have scope :arg1 */
    public function theAuthorizationRecordShouldHaveScope($arg1): void
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

    /** @Then the authorization record should not have scope :arg1 */
    public function theAuthorizationRecordShouldNotHaveScope($arg1): void
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

    /** @Given I have approved an authorization request with scope parameter :arg1 */
    public function iHaveApprovedAnAuthorizationRequestWithScopeParameter($arg1): void
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

    /** @When the client requests a token */
    public function theClientRequestsAToken(): void
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

    /** @Then the json :arg1 parameter should match the recorded access token */
    public function theJsonParameterShouldMatchTheRecordedAccessToken($arg1): void
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

    /** @Given I receive a token request with no grant_type parameter */
    public function iReceiveATokenRequestWithNoGrantTypeParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
            // 'grant_type' => 'authorization_code',
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @Given I receive a token request with grant_type parameter :arg1 */
    public function iReceiveATokenRequestWithGrantTypeParameter($arg1): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => $arg1,
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @Given I receive a token request with no client_id parameter */
    public function iReceiveATokenRequestWithNoClientIdParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
            //'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @Given I receive a token request with no redirect_uri parameter */
    public function iReceiveATokenRequestWithNoRedirectUriParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => 'http://localhost/fake/',
            //'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @Given I receive a token request with no code parameter */
    public function iReceiveATokenRequestWithNoCodeParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
            //'code' => 'test',
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @Given I receive a token request with no me parameter */
    public function iReceiveATokenRequestWithNoMeParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => 'test',
            //'me' => 'http://localhost/'
            ]
        );
    }

    /** @Given I have not approved an authorization request */
    public function iHaveNotApprovedAnAuthorizationRequest(): void
    {
        $this->iHaveNotApprovedAnAuthenticationRequest();
    }

    /** @When I receive a token request */
    public function iReceiveATokenRequest(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => 'http://localhost/fake/',
                'redirect_uri' => 'http://localhost/fake/',
                'code' => $this->_indieauth_code ?? 'test',
                'me' => 'http://localhost/',
            ]
        );
        $response = json_decode($this->getSession()->getPage()->getContent());
        assertNotFalse($response);

        if (! isset($response->access_token)) {
            return;
        }

        $this->_indieauth_token = $response->access_token;
    }

    /** @Given a duplicate access token request was made */
    public function aDuplicateAccessTokenRequestWasMade(): void
    {
        $this->iReceiveATokenRequest();
    }

    /** @Given I have approved an authorization request more than ten minutes ago */
    public function iHaveApprovedAnAuthorizationRequestMoreThanTenMinutesAgo(): void
    {
        $this->iHaveApprovedAnAuthenticationRequestMoreThanTenMinutesAgo();
    }

    /** @Given I have approved an authorization request */
    public function iHaveApprovedAnAuthorizationRequest(): void
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

    /** @Given no tokens have been issued */
    public function noTokensHaveBeenIssued(): void
    {
        $files = glob(VAR_ROOT . 'indieauth/token-*');

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            unlink($file);
        }
    }

    /** @When I receive a token revocation request */
    public function iReceiveATokenRevocationRequest(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'action' => 'revoke',
                'token' => $this->_indieauth_token ?? 'test',
            ]
        );
    }

    /** @Given the access token has been revoked */
    public function theAccessTokenHasBeenRevoked(): void
    {
        $this->iReceiveATokenRevocationRequest();
    }

    /** @When I receive a token verification request that is missing the bearer token */
    public function iReceiveATokenVerificationRequestThatIsMissingTheBearerToken(): void
    {
        $this->getSession()->visit('/token/');
    }

    /** @When I receive a token verification request */
    public function iReceiveATokenVerificationRequest(): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $this->getSession()->visit('/token/');
    }

    /** @Then the response should be empty */
    public function theResponseShouldBeEmpty(): void
    {
        assertEmpty($this->getSession()->getPage()->getContent());
    }

    /** @Given an access token has been issued */
    public function anAccessTokenHasBeenIssued(): void
    {
        $this->iReceiveATokenRequest();
        assertFileExists(VAR_ROOT . 'indieauth/token-' . $this->_indieauth_token);
    }

    /** @Given an access token has been issued to micropub.rocks */
    public function anAccessTokenHasBeenIssuedToMicropubRocks(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/token/',
            [
                'grant_type' => 'authorization_code',
                'client_id' => 'https://micropub.rocks/fake/',
                'redirect_uri' => 'https://micropub.rocks/fake/',
                'code' => $this->_indieauth_code,
                'me' => 'http://localhost/',
            ]
        );
        $response = json_decode($this->getSession()->getPage()->getContent());
        assertNotFalse($response);

        if (isset($response->access_token)) {
            $this->_indieauth_token = $response->access_token;
        }

        assertFileExists(VAR_ROOT . 'indieauth/token-' . $this->_indieauth_token);
    }

    /** @Then the token should be marked as revoked */
    public function theTokenShouldBeMarkedAsRevoked(): void
    {
        $token = yaml_parse_file(VAR_ROOT . 'indieauth/token-' . $this->_indieauth_token);
        assertNotFalse($token);
        assertArrayHasKey('revoked', $token);
    }

    /** @Given I use the indieauth-client library */
    public function iUseTheIndieauthClientLibrary(): void
    {
    }

    /** @When the client tries to discover the micropub endpoint */
    public function theClientTriesToDiscoverTheMicropubEndpoint(): void
    {
        $this->_micropub_endpoint = IndieAuth\Client::discoverMicropubEndpoint($this->base_url);
        assertNotEmpty($this->_micropub_endpoint);
    }

    /** @Then the micropub endpoint is base_url plus :arg1 */
    public function theMicropubEndpointIsBaseUrlPlus($arg1): void
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_micropub_endpoint);
    }

    /** @When I receive a micropub request */
    public function iReceiveAMicropubRequest(): void
    {
        $this->getSession()->visit('/micropub/?access_token=' . ($this->_indieauth_token ?? 'test'));
    }

    /** @Given I receive a micropub request via get that has no access token */
    public function iReceiveAMicropubRequestViaGetThatHasNoAccessToken(): void
    {
        $this->getSession()->visit('/micropub/');
    }

    /** @When I receive a micropub request that has no access token from micropub.rocks */
    public function iReceiveAMicropubRequestThatHasNoAccessTokenFromMicropubRocks(): void
    {
        $this->iReceiveAMicropubRequestViaGetThatHasNoAccessToken();
    }

    /** @Given I receive a micropub request via post that has no access token */
    public function iReceiveAMicropubRequestViaPostThatHasNoAccessToken(): void
    {
        $this->getSession()->getDriver()->getClient()->request('GET', '/micropub/');
    }

    /** @Given I receive a micropub request that has both header and parameter access tokens */
    public function iReceiveAMicropubRequestThatHasBothHeaderAndParameterAccessTokens(): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer test');
        $this->getSession()->visit('/micropub/?access_token=test');
    }

    /** @Given I have authorized micropub.rocks */
    public function iHaveAuthorizedMicropubRocks(): void
    {
        $this->iAmLoggedIn();
        $authorization_url = IndieAuth\Client::buildAuthorizationURL(
            IndieAuth\Client::discoverAuthorizationEndpoint($this->base_url),
            $this->base_url,
            'https://micropub.rocks/fake/',
            'https://micropub.rocks/fake/',
            'test',
            'create',
            'secret'
        );
        $this->getSession()->visit($authorization_url);
        $this->getSession()->getPage()->findButton('Continue')->press();
        $redirect_url = parse_url($this->getSession()->getCurrentUrl());
        parse_str($redirect_url['query'], $params);
        $this->_indieauth_code = $params['code'];
        $this->resetSession();
    }

    /** @When I receive a micropub request that has both header and parameter access tokens from micropub.rocks */
    public function iReceiveAMicropubRequestThatHasBothHeaderAndParameterAccessTokensFromMicropubRocks(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'access_token' => ($this->_indieauth_token ?? 'test'),
                'content' => 'the content',
            ],
            // files
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . ($this->_indieauth_token ?? 'test'),
            ],
        );
    }

    /** @Given I receive a configuration query */
    public function iReceiveAConfigurationQuery(): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $this->getSession()->visit('/micropub/?q=config');
    }

    /** @When I receive a micropub request to create a post */
    public function iReceiveAMicropubRequestToCreateAPost(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'content' => 'hello world',
                'access_token' => $this->_indieauth_token ?? 'test',
            ]
        );
    }

    /** @When I receive a micropub request from micropub.rocks */
    public function iReceiveAMicropubRequestFromMicropubRocks(): void
    {
        $this->iReceiveAMicropubRequestToCreateAPost();
    }

    /** @Given I receive a micropub request to create a post that has no content parameter */
    public function iReceiveAMicropubRequestToCreateAPostThatHasNoContentParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'access_token' => $this->_indieauth_token ?? 'test',
            ]
        );
    }

    /** @Given I receive a micropub request to create a post that has no h parameter */
    public function iReceiveAMicropubRequestToCreateAPostThatHasNoHParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'content' => 'hello world',
                'access_token' => $this->_indieauth_token ?? 'test',
            ]
        );
    }

    /** @Then there should be a HTTP location header with the post permalink */
    public function thereShouldBeAHttpLocationHeaderWithThePostPermalink(): void
    {
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $permalink = $headers['Location'][0];
        assertEquals(1, preg_match('/[0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/$/', $permalink));
    }

    /** @Given I create a new micropub post with content :arg1 */
    public function iCreateANewMicropubPostWithContent($arg1): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'content' => $arg1,
                'access_token' => $this->_indieauth_token ?? 'test',
            ]
        );
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_micropub_post_permalink = $headers['Location'][0];
    }

    /** @When I visit the post permalink */
    public function iVisitThePostPermalink(): void
    {
        $this->getSession()->visit($this->_micropub_post_permalink);
    }

    /** @Given I have received a micropub request to create: */
    public function iHaveReceivedAMicropubRequestToCreate(TableNode $table): void
    {
        $post_params = [
            'access_token' => $this->_indieauth_token ?? 'test',
        ];

        foreach ($table->getHash() as $row) {
            if (substr($row['parameter'], -2) == '[]') {
                $post_params[substr($row['parameter'], 0, strlen($row['parameter']) - 2)][] = $row['value'];
                continue;
            }

            $post_params[$row['parameter']] = $row['value'];
        }

        $this->getSession()->getDriver()->getClient()->request('POST', '/micropub/', $post_params);
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_micropub_post_permalink = $headers['Location'][0];
    }

    /** @Then the post record should have yaml front matter */
    public function thePostRecordShouldHaveYamlFrontMatter(): void
    {
        assertEquals(1, preg_match('/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)$/', $this->_micropub_post_permalink, $matches));
        assertFileExists(CONTENT_ROOT . $matches[1] . 'web.foo');
        $post_record = file_get_contents(CONTENT_ROOT . $matches[1] . 'web.foo');
        assertEquals(1, preg_match('/^(?m)(---$.*^...)$/Us', $post_record, $yaml));
        $this->_front_matter = yaml_parse($yaml[1]);
        $this->_post_content = trim(substr($post_record, strlen($yaml[1])));
        assertNotFalse($this->_front_matter);
    }

    /** @Then the yaml should have a :arg1 key with value :arg2 */
    public function theYamlShouldHaveAKeyWithValue($arg1, $arg2): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertEquals($arg2, $this->_front_matter[$arg1]);
    }

    /** @Then the yaml should have a nested array in :arg1 with a nested array in :arg2 with an element :arg3 */
    public function theYamlShouldHaveANestedArrayInWithANestedArrayInWithAnElement($arg1, $arg2, $arg3): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertIsArray($this->_front_matter[$arg1]);
        assertArrayHasKey($arg2, $this->_front_matter[$arg1]);
        assertIsArray($this->_front_matter[$arg1][$arg2]);
        assertContains($arg3, $this->_front_matter[$arg1][$arg2]);
    }

    /** @Then the yaml should have a nested array in :arg1 with a nested array in :arg2 with a nested array in :arg3 with an element :arg4 */
    public function theYamlShouldHaveANestedArrayInWithANestedArrayInWithANestedArrayInWithAnElement($arg1, $arg2, $arg3, $arg4): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertIsArray($this->_front_matter[$arg1]);
        assertArrayHasKey($arg2, $this->_front_matter[$arg1]);
        assertIsArray($this->_front_matter[$arg1][$arg2]);
        assertArrayHasKey($arg3, $this->_front_matter[$arg1][$arg2]);
        assertIsArray($this->_front_matter[$arg1][$arg2][$arg3]);
        assertContains($arg4, $this->_front_matter[$arg1][$arg2][$arg3]);
    }

    /** @Then the yaml should have a nested array in :arg1 with a nested array in :arg2 with a nested array in :arg3 with an element the post permalink */
    public function theYamlShouldHaveANestedArrayInWithANestedArrayInWithANestedArrayInWithAnElementThePostPermalink($arg1, $arg2, $arg3): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertIsArray($this->_front_matter[$arg1]);
        assertArrayHasKey($arg2, $this->_front_matter[$arg1]);
        assertIsArray($this->_front_matter[$arg1][$arg2]);
        assertArrayHasKey($arg3, $this->_front_matter[$arg1][$arg2]);
        assertIsArray($this->_front_matter[$arg1][$arg2][$arg3]);
        assertContains($this->_micropub_post_permalink, $this->_front_matter[$arg1][$arg2][$arg3]);
    }

    /** @Then the yaml should have a nested array in :arg1 with a nested array in :arg2 with a nested array in :arg3 with an element that ends with :arg4 */
    public function theYamlShouldHaveANestedArrayInWithANestedArrayInWithANestedArrayInWithAnElementThatEndsWith($arg1, $arg2, $arg3, $arg4): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertIsArray($this->_front_matter[$arg1]);
        assertArrayHasKey($arg2, $this->_front_matter[$arg1]);
        assertIsArray($this->_front_matter[$arg1][$arg2]);
        assertArrayHasKey($arg3, $this->_front_matter[$arg1][$arg2]);
        assertIsArray($this->_front_matter[$arg1][$arg2][$arg3]);
        $found = false;
        $arg4 = strrev($arg4);

        foreach ($this->_front_matter[$arg1][$arg2][$arg3] as $value) {
            if (strpos(strrev($value), $arg4) !== 0) {
                continue;
            }

            $found = true;
        }

        assertTrue($found);
    }

    /** @Then the yaml nested array in :arg1 with a nested array in :arg2 should not have a key :arg3 */
    public function theYamlNestedArrayInWithANestedArrayInShouldNotHaveAKey($arg1, $arg2, $arg3): void
    {
        assertArrayHasKey($arg1, $this->_front_matter);
        assertIsArray($this->_front_matter[$arg1]);
        assertArrayHasKey($arg2, $this->_front_matter[$arg1]);
        assertIsArray($this->_front_matter[$arg1][$arg2]);
        assertArrayNotHasKey($arg3, $this->_front_matter[$arg1][$arg2]);
    }

    /** @Then the post record should have content following the front matter */
    public function thePostRecordShouldHaveContentFollowingTheFrontMatter(): void
    {
        assertNotEmpty($this->_post_content);
    }

    /** @Then the post record content should be :arg1 */
    public function thePostRecordContentShouldBe($arg1): void
    {
        assertEquals($arg1, $this->_post_content);
    }

    /** @When I receive a source query for the post */
    public function iReceiveASourceQueryForThePost(): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $this->getSession()->visit('/micropub/?q=source&url=' . $this->_micropub_post_permalink);
    }

    /** @When I receive a source query that is missing the URL parameter */
    public function iReceiveASourceQueryThatIsMissingTheUrlParameter(): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $this->getSession()->visit('/micropub/?q=source');
    }

    /** @When I receive a source query for :arg1 */
    public function iReceiveASourceQueryForUrl($arg1): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $this->getSession()->visit('/micropub/?q=source&url=' . $arg1);
    }

    /** @When I receive a source query for the post properties :arg1 */
    public function iReceiveASourceQueryForThePostProperties($arg1): void
    {
        $this->getSession()->setRequestHeader('Authorization', 'Bearer ' . ($this->_indieauth_token ?? 'test'));
        $url = '/micropub/?q=source&url=' . $this->_micropub_post_permalink;

        foreach (explode(' ', $arg1) as $property) {
            $url .= '&properties[]=' . $property;
        }

        $this->getSession()->visit($url);
    }

    /** @Given I have received a micropub request with embedded media to create: */
    public function iHaveReceivedAMicropubRequestWithEmbeddedMediaToCreate(TableNode $table): void
    {
        $post_params = [
            'access_token' => $this->_indieauth_token ?? 'test',
        ];

        foreach ($table->getHash() as $row) {
            if (substr($row['parameter'], -2) == '[]') {
                if (strpos($row['value'], 'from_file:') === 0) {
                    $value = trim(substr($row['value'], 10));
                    $files[substr($row['parameter'], 0, strlen($row['parameter']) - 2)][] = FIXTURES_ROOT . 'media/' . $value;
                    continue;
                }

                $post_params[substr($row['parameter'], 0, strlen($row['parameter']) - 2)][] = $row['value'];
                continue;
            }

            if (strpos($row['value'], 'from_file:') === 0) {
                $value = trim(substr($row['value'], 10));
                $files[$row['parameter']] = FIXTURES_ROOT . 'media/' . $value;
                continue;
            }

            $post_params[$row['parameter']] = $row['value'];
        }

        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            $post_params,
            $files ?? [],
        );
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_micropub_post_permalink = $headers['Location'][0];
    }

    /** @Given I have received a micropub request with embedded photo */
    public function iHaveReceivedAMicropubRequestWithEmbeddedPhoto(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'access_token' => $this->_indieauth_token ?? 'test',
            ],
            ['photo' => FIXTURES_ROOT . 'media/0-1.jpg'],
        );
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_micropub_post_permalink = $headers['Location'][0];
    }

    /** @When I visit the photo url */
    public function iVisitThePhotoUrl(): void
    {
        $this->getSession()->visit($this->_micropub_post_permalink . 'media/photo1.jpg');
    }

    /** @Given I receive a JSON-encoded micropub request to create a post that has no h parameter */
    public function iReceiveAJsonEncodedMicropubRequestToCreateAPostThatHasNoHParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [],
            // files
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . ($this->_indieauth_token ?? 'test'),
            ],
            json_encode(
                [
                    'content' => 'hello world',
                ]
            )
        );
    }

    /** @Given I have received a JSON-encoded micropub request to create: */
    public function iHaveReceivedAJsonEncodedMicropubRequestToCreate(PyStringNode $string): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [],
            // files
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . ($this->_indieauth_token ?? 'test'),
            ],
            $string,
        );
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_micropub_post_permalink = $headers['Location'][0];
    }

    /** @Then there should be a :arg1 element with text content :arg2 */
    public function thereShouldBeAElementWithTextContent($arg1, $arg2): void
    {
        $results = $this->getSession()->getPage()->findAll('css', $arg1);
        assertNotEmpty($results);
        $found = false;

        foreach ($results as $node) {
            if ($arg2 != $node->getText()) {
                continue;
            }

            $found = true;
        }

        assertTrue($found);
    }

    /** @Given I use the mention-client library */
    public function iUseTheMentionClientLibrary(): void
    {
    }

    /** @When the client tries to discover the webmention endpoint */
    public function theClientTriesToDiscoverTheWebmentionEndpoint(): void
    {
        $client = new IndieWeb\MentionClient();
        $this->_webmention_url = $client->discoverWebmentionEndpoint($this->base_url);
        assertNotEmpty($this->_webmention_url);
    }

    /** @Then the webmention endpoint is base_url plus :arg1 */
    public function theWebmentionEndpointIsBaseUrlPlus($arg1): void
    {
        assertEquals(rtrim($this->base_url, '/') . $arg1, $this->_webmention_url);
    }

    /** @Given I receive a webmention that has no target parameter */
    public function iReceiveAWebmentionThatHasNoTargetParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/webmention/',
            [
            // 'target' => 'http://localhost/',
                'source' => 'http://localhost/',
            ],
        );
    }

    /** @Given I receive a webmention that has no source parameter */
    public function iReceiveAWebmentionThatHasNoSourceParameter(): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/webmention/',
            [
                'target' => 'http://localhost/',
            // 'source' => 'http://localhost/',
            ],
        );
    }

    /** @Given I receive a webmention that has an empty target parameter */
    public function iReceiveAWebmentionThatHasAnEmptyTargetParameter(): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter('', 'http://localhost/');
    }

    /** @Given I receive a webmention that has an empty source parameter */
    public function iReceiveAWebmentionThatHasAnEmptySourceParameter(): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter('http://localhost/', '');
    }

    /** @Given I receive a webmention that has a target parameter :arg1 */
    public function iReceiveAWebmentionThatHasATargetParameter($arg1): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter($arg1, 'http://localhost/');
    }

    /** @Given I receive a webmention that has a target parameter base_url plus :arg1 */
    public function iReceiveAWebmentionThatHasATargetParameterBaseUrlPlus($arg1): void
    {
        $this->iReceiveAWebmentionThatHasATargetParameter($this->base_url . $arg1);
    }

    /** @Given I receive a webmention that has a source parameter :arg1 */
    public function iReceiveAWebmentionThatHasASourceParameterSource($arg1): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter('http://localhost/', $arg1);
    }

    /** @Given I receive a webmention that has target parameter :arg1 and source parameter :arg2 */
    public function iReceiveAWebmentionThatHasTargetParameterAndSourceParameter($arg1, $arg2): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/webmention/',
            [
                'target' => $arg1,
                'source' => $arg2,
            ]
        );
    }

    /** @Given I have created a new post with content :arg1 */
    public function iHaveCreatedANewPostWithContent($arg1): void
    {
        $this->iCreateANewMicropubPostWithContent($arg1);
    }

    /** @When I receive a webmention that has the post permalink in the target and source :arg1 */
    public function iReceiveAWebmentionThatHasThePostPermalinkInTheTargetAndSource($arg1): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter($this->_micropub_post_permalink, $arg1);
    }

    /** @Given I have no incoming webmentions spooled for verification */
    public function iHaveNoIncomingWebmentionsSpooledForVerification(): void
    {
        $files = glob(VAR_ROOT . 'webmention/incoming-*');

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            unlink($file);
        }
    }

    /** @When I receive a webmention from my own site that has the post permalink in the target */
    public function iReceiveAWebmentionFromMyOwnSiteThatHasThePostPermalinkInTheTarget(): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter($this->_micropub_post_permalink, 'http://localhost/fake/');
    }

    /** @When I receive a webmention from :arg1 that has the post permalink in the target */
    public function iReceiveAWebmentionFromThatHasThePostPermalinkInTheTarget($arg1): void
    {
        $this->iReceiveAWebmentionThatHasTargetParameterAndSourceParameter($this->_micropub_post_permalink, $arg1);
    }

    /** @Then there should be an incoming webmention spooled for verification */
    public function thereShouldBeAnIncomingWebmentionSpooledForVerification(): void
    {
        $spooled = glob(VAR_ROOT . 'webmention/incoming-*');
        assertNotEmpty($spooled);
    }

    private function _spoolIncomingWebmention($target, $source): void
    {
        $webmention = [
            'target' => $target,
            'source' => $source,
            'request' => [
                'query_string' => '',
                'referer' => '',
                'user_agent' => '',
                'remote_addr' => '',
            ],
        ];
        yaml_emit_file(VAR_ROOT . 'webmention/incoming-' . uniqid(), $webmention);
    }

    /** @Given I have an incoming webmention spooled for verification */
    public function iHaveAnIncomingWebmentionSpooledForVerification(): void
    {
        $this->_spoolIncomingWebmention('http://localhost/fake/', 'http://localhost/fake/');
    }

    /** @Then I should have no incoming webmentions spooled for verification */
    public function iShouldHaveNoIncomingWebmentionsSpooledForVerification(): void
    {
        $spooled = glob(VAR_ROOT . 'webmention/incoming-*');
        assertEmpty($spooled);
    }

    /** @Given an async operation will start on the next visit */
    public function anAsyncOperationWillStartOnTheNextVisit(): void
    {
        assertFileNotExists(VAR_ROOT . 'async/.async-active');

        if (file_exists(VAR_ROOT . 'async/.async-last')) {
            assertTrue(unlink(VAR_ROOT . 'async/.async-last'));
        }

        file_put_contents(VAR_ROOT . 'async/.async-last', time() - 30);
        chmod(VAR_ROOT . 'async/.async-last', 0777);
    }

    /** @Given an async operation will not start on the next visit */
    public function anAsyncOperationWillNotStartOnTheNextVisit(): void
    {
        assertFileNotExists(VAR_ROOT . 'async/.async-active');

        if (file_exists(VAR_ROOT . 'async/.async-last')) {
            assertTrue(unlink(VAR_ROOT . 'async/.async-last'));
        }

        file_put_contents(VAR_ROOT . 'async/.async-last', time());
        chmod(VAR_ROOT . 'async/.async-last', 0777);
    }

    /** @Given I have an incoming webmention spooled for verification with target base_url plus :arg1 */
    public function iHaveAnIncomingWebmentionSpooledForVerificationWithTargetBaseUrlPlus($arg1): void
    {
        $this->_spoolIncomingWebmention($this->base_url . 'fake/', 'http://localhost/fake2/');
    }

    /** @Given I have an incoming webmention spooled for verification with target the post permalink and source :arg1 */
    public function iHaveAnIncomingWebmentionSpooledForVerificationWithTargetThePostPermalinkAndSource($arg1): void
    {
        assertNotEmpty($this->_micropub_post_permalink);
        $this->_spoolIncomingWebmention($this->_micropub_post_permalink, $arg1);
    }

    /** @Then the post record should have no webmentions */
    public function thePostRecordShouldHaveNoWebmentions(): void
    {
        assertEquals(1, preg_match('/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)$/', $this->_micropub_post_permalink, $matches));
        assertFileNotExists(CONTENT_ROOT . $matches[1] . 'web.mentions');
    }

    /** @Given I have created a webmention source post with content :arg1 */
    public function iHaveCreatedAWebmentionSourcePostWithContent($arg1): void
    {
        $this->getSession()->getDriver()->getClient()->request(
            'POST',
            '/micropub/',
            [
                'content' => $arg1,
                'access_token' => $this->_indieauth_token ?? 'test',
            ]
        );
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Location', $headers);
        $this->_webmention_source_permalink = $headers['Location'][0];
    }

    /** @Given I have created a webmention source post with content the post permalink */
    public function iHaveCreatedAWebmentionSourcePostWithContentThePostPermalink(): void
    {
        $this->iHaveCreatedAWebmentionSourcePostWithContent($this->_micropub_post_permalink);
    }

    /** @Then the post record should have webmentions */
    public function thePostRecordShouldHaveWebmentions(): void
    {
        assertEquals(1, preg_match('/([0-9]{4}\/(?:(?:0[0-9])|1[0-2])\/(?:(?:[012][0-9])|3[0-1])\/[0-9]+\/)$/', $this->_micropub_post_permalink, $matches));
        assertFileExists(CONTENT_ROOT . $matches[1] . 'web.mentions');
        $this->_post_webmentions = yaml_parse_file(CONTENT_ROOT . $matches[1] . 'web.mentions');
        assertNotFalse($this->_post_webmentions);
    }

    /** @Given I have an incoming webmention spooled for verification with target the post permalink and source the webmention source post permalink */
    public function iHaveAnIncomingWebmentionSpooledForVerificationWithTargetThePostPermalinkAndSourceTheWebmentionSourcePostPermalink(): void
    {
        assertNotEmpty($this->_micropub_post_permalink);
        assertNotEmpty($this->_webmention_source_permalink);
        $this->_spoolIncomingWebmention($this->_micropub_post_permalink, $this->_webmention_source_permalink);
    }

    /** @Then there should be :arg1 generic webmentions */
    public function thereShouldBeGenericWebmentions($arg1): void
    {
        assertEquals(1, $this->_post_webmentions['generic']['count']);
    }

    /** @Then the list of generic webmentions should contain the webmention source post permalink */
    public function theListOfGenericWebmentionsShouldContainTheWebmentionSourcePostPermalink(): void
    {
        assertContains($this->_webmention_source_permalink, $this->_post_webmentions['generic']['items']);
    }

    /** @Then there should be :arg1 repost webmentions */
    public function thereShouldBeRepostWebmentions($arg1): void
    {
        assertEquals(0, $this->_post_webmentions['repost']['count']);
    }

    /** @Then there should be :arg1 response webmentions */
    public function thereShouldBeResponseWebmentions($arg1): void
    {
        assertEquals(0, $this->_post_webmentions['response']['count']);
    }

    /** @Then there should be :arg1 like webmentions */
    public function thereShouldBeLikeWebmentions($arg1): void
    {
        assertEquals(0, $this->_post_webmentions['response']['items']['like']['count']);
    }

    /** @Then there should be :arg1 bookmark webmentions */
    public function thereShouldBeBookmarkWebmentions($arg1): void
    {
        assertEquals(0, $this->_post_webmentions['response']['items']['bookmark']['count']);
    }

    /** @Then there should be :arg1 reply webmentions */
    public function thereShouldBeReplyWebmentions($arg1): void
    {
        assertEquals(0, $this->_post_webmentions['response']['items']['reply']['count']);
    }
}
