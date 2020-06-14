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
