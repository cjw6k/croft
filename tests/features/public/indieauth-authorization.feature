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
