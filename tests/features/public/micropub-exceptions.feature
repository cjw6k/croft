Feature: Passing the tests at micropub.rocks
	In order to pass the tests at micropub.rocks
	As a micropub server
	I must permit certain violations of the micropub spec

	@config_micropub_exceptions
	Scenario: Receiving a micropub request that has both header and parameter access tokens, from micropub.rocks
		Given I have authorized micropub.rocks
		And an access token has been issued to micropub.rocks
		When I receive a micropub request that has both header and parameter access tokens from micropub.rocks
		Then the response status code should be 201
		And there should be a HTTP location header with the post permalink

	@config_micropub_exceptions
	Scenario: Receiving a micropub request with no matching token record, from micropub.rocks
		Given I have not approved an authorization request
		And no tokens have been issued
		When I receive a micropub request from micropub.rocks
		Then the response status code should be 403
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request could not be matched to an authorized access token"

	@config_micropub_exceptions
	Scenario: Receiving a micropub request that is missing an access token, from micropub.rocks
		Given I have authorized micropub.rocks
		And an access token has been issued to micropub.rocks
		When I receive a micropub request that has no access token from micropub.rocks
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "unauthorized"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request did not provide an access token"
