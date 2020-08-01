@mink:goutte @cleanup_async
Feature: WebFoo sticks around to process requests after sending a complete response to the client
	In order to protect myself from technology abuses
	As a webfoo-site host
	I must have webfoo process certain tasks asyncronously

	Scenario: Starting asynchronous operations after completing a request
		Given No async operations are currently running
		And No async operation has run on this install
		When I visit the homepage
		And I wait 2 seconds
		Then there should be a marker of the last async operation start time
		And there should be no async operations currently running

	Scenario: Async operations do not overlap
		Given An async operation is currently running
		When I visit the homepage
		And I wait 2 seconds
		Then the last async operation start time should not be changed

	Scenario: Async operations do not run everytime
		Given No async operations are currently running
		And the last async operation started 5 seconds ago
		When I visit the homepage
		And I wait 2 seconds
		Then the last async operation start time should not be changed

	Scenario: Async operations start at least 30 seconds apart
		Given No async operations are currently running
		And the last async operation started 31 seconds ago
		When I visit the homepage
		And I wait 2 seconds
		Then the last async operation start time should be changed

	Scenario: Async operations do not overlap even when last started more than 30 seconds ago
		Given An async operation is currently running
		And the last async operation started 31 seconds ago
		When I visit the homepage
		And I wait 2 seconds
		Then the last async operation start time should not be changed
