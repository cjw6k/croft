Feature: WebFoo provides an indieauth server for logging into other sites
	In order to log in to other sites
	As a webfoo site owner
	I must authenticate with an indieauth client

	Scenario: Discovery available to clients with HTTP link header
		Given I am on "/"
		Then there should be an HTTP "Link" header with value '</auth/>; rel="authorization_endpoint"'

	Scenario: Discovery available to clients with HTML link element
		Given I am on "/"
		Then there should be a link element with rel "authorization_endpoint" and href "/auth/"

	Scenario: Discovery with the IndieAuth\Client library
		Given I start an IndieAuth authorization flow
		When the client tries to discover the authorization endpoint
		Then the authorization_endpoint is base_url plus "/auth/"

	Scenario: Receiving an authentication request while not logged in
		Given I am not logged in
		And I receive an authentication request
		Then I should be on "/login/"

	@user_exists
	Scenario: Receiving an authentication request while logged in
		Given I am logged in
		And I receive an authentication request
		Then I should be on "/auth/"
		And I should see "Do you want to log in to "
		And I should see "You will be redirected to "
		And I should see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with missing user profile URL
		Given I am logged in
		And I receive an authentication request
		But the authentication request has no me parameter
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "missing required user profile URL (me) parameter"
		And I should not see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with missing path component user profile URL
		Given I am logged in
		And I receive an authentication request
		But the authentication request has me parameter "http://localhost"
		Then I should see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with unmatched user profile URL
		Given I am logged in
		And I receive an authentication request
		But the authentication request has me parameter "http://localhost/nope/"
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "the requested user profile URL (me) is not valid here"
		And I should not see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with matched user profile URL
		Given I am logged in
		And I receive an authentication request
		Then I should see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with missing client_id
		Given I am logged in
		And I receive an authentication request
		But the authentication request has no client_id parameter
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "missing required client_id parameter"
		And I should not see "Continue"

	@user_exists
	Scenario Outline: Receiving an authentication request with non-conformal client_id
		Given I am logged in
		And I receive an authentication request
		But the authentication request has client_id <client_id>
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see <error_message>
		And I should not see "Continue"

		Examples:
		  | client_id                           | error_message                                                |
		  | "http:///example.com"               | "client_id must be a URL"                                    |
		  | "cashew rope"                       | "client_id must be a URL"                                    |
		  | "example.com/"                      | "client_id must be a URL"                                    |
		  | "gopher://example.com/"             | "client_id must use HTTP or HTTPS"                           |
		  | "foo://example.com/"                | "client_id must use HTTP or HTTPS"                           |
		  | "https://example.com"               | "client_id must include a path"                              |
		  | "https://example.com/h/./"          | "client_id must not include relative components in the path" |
		  | "https://example.com/h/../"         | "client_id must not include relative components in the path" |
		  | "https://example.com/#i"            | "client_id must not contain a fragment"                      |
		  | "https://a@example.com/"            | "client_id must not contain a username or password"          |
		  | "https://:b@example.com/"           | "client_id must not contain a username or password"          |
		  | "https://a:b@example.com/"          | "client_id must not contain a username or password"          |
		  | "https://10.0.0.1/"                 | "client_id must not be an IPV4 address"                      |
		  | "https://[fdbf:67b7:26e1:7146::1]/" | "client_id must not be an IPV6 address"                      |
		  | "https://[]/"                       | "client_id must be a URL"                                    |

	@user_exists
	Scenario: Receiving an authentication request with missing redirect_uri
		Given I am logged in
		And I receive an authentication request
		But the authentication request has no redirect_uri parameter
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "missing required redirect_uri parameter"
		And I should not see "Continue"

	@user_exists
	Scenario Outline: Receiving an authentication request with non-conformal redirect_uri
		Given I am logged in
		And I receive an authentication request
		But the authentication request has redirect_uri parameter <redirect_uri>
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see <error_message>
		And I should not see "Continue"

		Examples:
		  | redirect_uri            | error_message                         |
		  | "http:///example.com"   | "redirect_uri must be a URL"          |
		  | "cashew rope"           | "redirect_uri must be a URL"          |
		  | "example.com/"          | "redirect_uri must be a URL"          |
		  | "gopher://example.com/" | "redirect_uri must use HTTP or HTTPS" |
		  | "foo://example.com/"    | "redirect_uri must use HTTP or HTTPS" |

	@user_exists
	Scenario Outline: Receiving an authentication request with mismatched client_id and redirect_uri
		Given I am logged in
		And I receive an authentication request
		But the authentication request has redirect_uri parameter <redirect_uri> with client_id <client_id>
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see <error_message>
		And I should not see "Continue"

		Examples:
		  | redirect_uri               | client_id                   | error_message                                           |
		  | "http://example.com/"      | "http://example.net/"       | "client_id and redirect_uri must be on the same domain" |
		  | "http://moon.example.com/" | "http://sun.example.com/"   | "client_id and redirect_uri must be on the same domain" |
		  | "http://example.com/"      | "http://stars.example.com/" | "client_id and redirect_uri must be on the same domain" |
		  | "https://example.com/"     | "http://example.com/"       | "client_id and redirect_uri must be on the same domain" |
		  | "http://example.com:80/"   | "http://example.com:81/"    | "client_id and redirect_uri must be on the same domain" |

	@user_exists
	Scenario Outline: Receiving an authentication request with conformal, matched client_id and redirect_uri
		Given I am logged in
		And I receive an authentication request
		And the authentication request has redirect_uri parameter <redirect_uri> with client_id <client_id>
		Then I should see "Continue"

		Examples:
		  | redirect_uri                      | client_id                    |
		  | "https://example.com/"            | "HTTPS://example.com/"       |
		  | "https://example.com:8081"        | "https://example.com:8081/"  |
		  | "http://localhost/"               | "http://localhost/"          |
		  | "http://127.0.0.1/"               | "http://127.0.0.1/"          |
		  | "http://[::1]/"                   | "http://[::1]/"              |
		  | "https://example.com"             | "https://example.com/?h"     |
		  | "https://example.com"             | "https://example.com/?h=h"   |
		  | "https://example.com"             | "https://example.com/?h=h&p" |
		  | "https://private-dns"             | "https://private-dns/"       |
		  | "https://stars.example.com"       | "https://example.com/"       |
		  | "https://deneb.stars.example.com" | "https://stars.example.com/" |

	@user_exists
	Scenario: Receiving an authentication request with missing state parameter
		Given I am logged in
		And I receive an authentication request
		But the authentication request has no state parameter
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "missing required state parameter"
		And I should not see "Continue"

	@user_exists
	Scenario: Receiving an authentication request with scope parameter
		Given I am logged in
		And I receive an authentication request
		And the authentication request has scope parameter "create update delete"
		Then I should see "Nope"
		And I should see "The authentication request was missing some stuff that makes it good."
		And I should see "scope is for authorization but this is authentication"
		And I should not see "Continue"

	@user_exists
	Scenario: Receiving an authentication request while not logged in, logging in and seeing the request
		Given I am not logged in
		And I receive an authentication request
		When I login
		Then I should be on "/auth/"
		And I should see "Continue"

	@user_exists
	Scenario: Approving an authentication request
		Given I am logged in
		And I receive an authentication request from "http://localhost/fake/"
		When I press "Continue"
		Then I should be on "http://localhost/fake/"

	@user_exists
	Scenario: Receiving an authorization code verification that is missing the code parameter
		Given I have approved an authentication request
		When I receive an authorization verification request
		But the authorization verification request has no code parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request was missing the code parameter"

	@user_exists
	Scenario: Receiving an authorization code verification that is missing the client_id parameter
		Given I have approved an authentication request
		When I receive an authorization verification request
		But the authorization verification request has no client_id parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request was missing the client_id parameter"

	@user_exists
	Scenario: Receiving an authorization code verification that is missing the redirect_uri parameter
		Given I have approved an authentication request
		When I receive an authorization verification request
		But the authorization verification request has no redirect_uri parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request was missing the redirect_uri parameter"

	Scenario: Receiving an authorization code verification when no authentication request was authorized
		Given I have not approved an authentication request
		When I receive an authorization verification request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request could not be matched to an approved authentication response"

	@user_exists
	Scenario: Receiving an authorization code verification that has been previously authorized
		Given I have approved an authentication request
		When I receive an authorization verification request
		Then the response status code should be 200
		And the response should be json
		And the json should not have an "error" parameter
		And the json should not have an "error_description" parameter
		And the json should have a "me" parameter
		And the json "me" parameter should be "http://localhost/"
		And the authorization code should be marked as having been used

	@user_exists
	Scenario: Receiving a duplicate authorization code verification that has been previously authorized
		Given I have approved an authentication request
		When I receive an authorization verification request
		And I receive an authorization verification request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request matched an approved authentication response that has already been used"
		And the json should not have a "me" parameter
		And the authorization code should be marked as having been used twice

	@user_exists
	Scenario: Receiving an authorization code verification when authorization has already expired
		Given I have approved an authentication request more than ten minutes ago
		When I receive an authorization verification request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the authorization code verification request matched an approved authentication response that has already expired (10 mins)"
		And the json should not have a "me" parameter
		And the authorization code should not be marked as having been used
