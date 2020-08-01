@mink:goutte
Feature: The 405 page provides information about method not allowed results
	In order to recover from a method not allowed result
	As a visitor
	I must reach the 405 page

	Scenario: Making a method not allowed result
		Given I make a POST request to the home page
		Then the response status code should be 405
		And I should see a link to "/"
		And the HTML should be valid

	Scenario: Clicking through to the homepage from a method not allowed result
		Given I make a POST request to the home page
		When I click on the link to "/"
		Then I should be on the homepage
