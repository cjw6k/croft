@mink:goutte
Feature: Managing content using third-party client applications with the Micropub method
	In order to manage my webfoo site
	As a micropub client user
	I must interact with my webfoo site using micropub

	Scenario: Discovery available to clients with HTTP link header
		Given I am on "/"
		Then there should be an HTTP "Link" header with value '</micropub/>; rel="micropub"'

	Scenario: Discovery available to clients with HTML link element
		Given I am on "/"
		Then there should be a link element with rel "micropub" and href "/micropub/"

	Scenario: Discovery with the IndieAuth\Client library
		Given I use the indieauth-client library
		When the client tries to discover the micropub endpoint
		Then the micropub endpoint is base_url plus "/micropub/"

	@user_exists
	Scenario: Receiving a micropub request with no matching token record
		Given I have not approved an authorization request
		And no tokens have been issued
		When I receive a micropub request
		Then the response status code should be 403
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request could not be matched to an authorized access token"

	Scenario: Receiving a micropub request via GET that is missing an access token
		Given I receive a micropub request via get that has no access token
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request did not provide an access token"

	Scenario: Receiving a micropub request via POST that is missing an access token
		Given I receive a micropub request via post that has no access token
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request did not provide an access token"

	Scenario: Receiving a micropub request that has both header and parameter access tokens
		Given I receive a micropub request that has both header and parameter access tokens
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request provided both header and parameter access tokens"

	@micropub_authorized
	Scenario: Responding to a configuration query
		Given I receive a configuration query
		Then the response status code should be 200
		And the response should be json
		And the json should have an "media-endpoint" parameter
		And the json "media-endpoint" parameter should be the empty string
		And the json should have an "syndicate-to" parameter
		And the json "syndicate-to" parameter should be the empty array

	@user_exists
	Scenario: Receiving a request to create a post without create scope
		Given I have approved an authorization request with scope parameter "update"
		And an access token has been issued
		When I receive a micropub request to create a post
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "insufficient_scope"
		And the json should have an "scope" parameter
		And the json "scope" parameter should be "create"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the access token must have 'create' scope to create a post"
		And there should not be a HTTP "Location" header

	@micropub_authorized
	Scenario: Receiving a request to create a post, that is missing the content parameter
		Given I receive a micropub request to create a post that has no content parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the content parameter is required to create a post"
		And there should not be a HTTP "Location" header

	@micropub_authorized
	Scenario: Receiving a request to create a post, that is missing the h parameter
		Given I receive a micropub request to create a post that has no h parameter
		Then the response status code should be 201
		And there should be a HTTP location header with the post permalink

	@micropub_authorized
	Scenario: Visiting a post permalink after authoring it with micropub
		Given I create a new micropub post with content "cashew rope"
		When I visit the post permalink
		Then the response status code should be 200
		And the HTML should be valid
		And I should see "cashew rope"
