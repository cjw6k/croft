@mink:goutte
Feature: WebFoo provides an indieauth server for clients to interact with a WebFoo site
	In order to permit clients to modify the site
	As a webfoo site owner
	I must authorize an indieauth client

	Scenario: Discovery available to clients with HTTP link header
		Given I am on "/"
		Then there should be an HTTP "Link" header with value '</token/>; rel="token_endpoint"'

	Scenario: Discovery available to clients with HTML link element
		Given I am on "/"
		Then there should be a link element with rel "token_endpoint" and href "/token/"

	Scenario: Discovery with the IndieAuth\Client library
		Given I start an IndieAuth authorization flow
		When the client tries to discover the token endpoint
		Then the token_endpoint is base_url plus "/token/"

	@user_exists
	Scenario: Receiving an authorization request with no defined scope
		Given I am logged in
		And I receive an authorization request
		Then I should see "The client requests the following access:"
		And the "scopes[]" checkbox with value "identity" should be checked
		And I should see "Continue"

	@user_exists
	Scenario: Receiving an authorization request with no defined scope
		Given I am logged in
		And I receive an authorization request
		But the authorization request is missing the scope parameter
		Then I should see "The client requests the following access:"
		And the "scopes[]" checkbox with value "identity" should be checked
		And I should see "Continue"

	@user_exists
	Scenario Outline: Receiving an authorization request with one defined scope
		Given I am logged in
		And I receive an authorization request with scope parameter <scope>
		Then I should see "The client requests the following access:"
		And the "scopes[]" checkbox with value <scope> should be checked
		And I should see "Continue"

		Examples:
		  | scope          |
		  | "create"       |
		  | "!create"      |
		  | "update"       |
		  | "update#"      |
		  | "delete"       |
		  | "DELETE"       |
		  | "media"        |
		  | "identity"     |
		  | "ext:en:sion"  |
		  | "[ext]en,sion" |

	@user_exists
	Scenario Outline: Receiving an authorization request with two defined scopes
		Given I am logged in
		And I receive an authorization request with scope parameter <scope>
		Then I should see "The client requests the following access:"
		And the "scopes[]" checkbox with value <scope1> should be checked
		And the "scopes[]" checkbox with value <scope2> should be checked
		And I should see "Continue"

		Examples:
		  | scope                | scope1        | scope2   |
		  | "_ -"                | "_"           | "-"      |
		  | "update delete"      | "update"      | "delete" |
		  | "ext:en:sion create" | "ext:en:sion" | "create" |

	@user_exists
	Scenario: Receiving an authorization request with lots of scopes
		Given I am logged in
		And I receive an authorization request with scope parameter "create update delete media cashew:rope"
		Then I should see "The client requests the following access:"
		And the "scopes[]" checkbox with value "create" should be checked
		And the "scopes[]" checkbox with value "update" should be checked
		And the "scopes[]" checkbox with value "delete" should be checked
		And the "scopes[]" checkbox with value "media" should be checked
		And the "scopes[]" checkbox with value "cashew:rope" should be checked
		And I should see "Continue"

	@user_exists
	Scenario: Approving an authorization request with all requested scopes
		Given I am logged in
		And I receive an authorization request with scope parameter "create update delete"
		When I press "Continue"
		Then I should be on "http://localhost/fake/"
		And the authorization record should have scope "create"
		And the authorization record should have scope "update"
		And the authorization record should have scope "delete"

	@user_exists
	Scenario: Approving an authorization request with not all of the requested scopes
		Given I am logged in
		And I receive an authorization request with scope parameter "create update delete"
		When I uncheck the "scopes[]" checkbox with value "delete"
		And I press "Continue"
		Then I should be on "http://localhost/fake/"
		And the authorization record should have scope "create"
		And the authorization record should have scope "update"
		And the authorization record should not have scope "delete"

	@user_exists
	Scenario: Receiving a token request with a missing grant_type parameter
		Given I receive a token request with no grant_type parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request was missing the grant_type parameter"

	@user_exists
	Scenario Outline: Receiving a token request with an invalid grant_type parameter
		Given I receive a token request with grant_type parameter <grant_type>
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "unsupported_grant_type"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the requested grant type is not supported here"

		Examples:
		  | grant_type    |
		  | "example"     |
		  | "sample"      |
		  | "cashew_rope" |

	@user_exists
	Scenario: Receiving a token request with a missing client_id parameter
		Given I receive a token request with no client_id parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request was missing the client_id parameter"

	@user_exists
	Scenario: Receiving a token request with a missing redirect_uri parameter
		Given I receive a token request with no redirect_uri parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request was missing the redirect_uri parameter"

	@user_exists
	Scenario: Receiving a token request with a missing code parameter
		Given I receive a token request with no code parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request was missing the code parameter"

	@user_exists
	Scenario: Receiving a token request with a missing user profile URL (me) parameter
		Given I receive a token request with no me parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request was missing the user profile URL (me) parameter"

	@user_exists
	Scenario: Receiving a token request with no matching authorization record
		Given I have not approved an authorization request
		When I receive a token request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request could not be matched to an approved authorization response"

	@user_exists
	Scenario: Receiving a token request when authorization has already expired
		Given I have approved an authorization request more than ten minutes ago
		When I receive a token request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request matched an approved authorization response that has already expired (10 mins)"
		And the json should not have a "access_token" parameter
		And the authorization code should not be marked as having been used

	@user_exists
	Scenario: Receiving a token request when the params match an approved authentication request with no specified scope
		Given I have approved an authentication request
		When I receive a token request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request matched an approved authentication response which authorizes no scopes"
		And the json should not have a "access_token" parameter
		And the authorization code should be marked as having been used

	@user_exists
	Scenario: Issuing a token
		Given I have approved an authorization request
		When I receive a token request
		Then the response status code should be 200
		And the response should be json
		And the json should have an "access_token" parameter
		And the json "access_token" parameter should match the recorded access token
		And the json should have an "token_type" parameter
		And the json "token_type" parameter should be "Bearer"
		And the json should have an "scope" parameter
		And the json "scope" parameter should be "create update delete"
		And the json should have an "me" parameter
		And the json "me" parameter should be "http://localhost/"
		And there should be an HTTP "Cache-Control" header with value "no-store"
		And there should be an HTTP "Pragma" header with value "no-cache"

	@user_exists
	Scenario: Receiving a duplicate token request when authorization has already been used
		Given I have approved an authorization request
		When I receive a token request
		And I receive a token request
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token request matched an approved authorization response that has already been used"
		And the json should not have a "access_token" parameter
		And the authorization code should be marked as having been used twice

	@user_exists
	Scenario: Receiving a token revocation request with no matching token record
		Given I have not approved an authorization request
		And no tokens have been issued
		When I receive a token revocation request
		Then the response status code should be 200
		And the response should be empty

	@user_exists
	Scenario: Receiving a token revocation request
		Given I have approved an authorization request
		And an access token has been issued
		When I receive a token revocation request
		Then the response status code should be 200
		And the response should be empty
		And the token should be marked as revoked

	@user_exists
	Scenario: Receiving a token verification request that is missing the bearer token
		Given I have approved an authorization request
		And an access token has been issued
		When I receive a token verification request that is missing the bearer token
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token verification request did not provided a bearer token"

	@user_exists
	Scenario: Receiving a token verification request with no matching token record
		Given I have not approved an authorization request
		And no tokens have been issued
		When I receive a token verification request
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token verification request could not be matched to an issued token"

	@user_exists
	Scenario: Receiving a token verification request with a revoked token
		Given I have approved an authorization request
		And an access token has been issued
		And the access token has been revoked
		When I receive a token verification request
		Then the response status code should be 403
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token verification request included a bearer token which has been revoked"

	@user_exists
	Scenario: Receiving a token verification request with a cancelled authorization
		Given I have approved an authorization request
		And an access token has been issued
		And a duplicate access token request was made
		When I receive a token verification request
		Then the response status code should be 403
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_grant"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the token verification request included a bearer token which originates from a cancelled authorization"

	@user_exists
	Scenario: Receiving a token verification request
		Given I have approved an authorization request
		And an access token has been issued
		When I receive a token verification request
		Then the response status code should be 200
		And the response should be json
		And the json should have an "me" parameter
		And the json "me" parameter should be "http://localhost/"
		And the json should have an "client_id" parameter
		And the json "client_id" parameter should be "http://localhost/fake/"
		And the json should have an "scope" parameter
		And the json "scope" parameter should be "create update delete"
