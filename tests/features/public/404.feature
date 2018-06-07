@mink:goutte
Feature: The 404 page provides information about file not found results
	In order to recover from a file not found result
	As a visitor
	I must reach the 404 page

	Scenario: Making a file not found result
		Given I am on a random path that doesn't exist
		Then the response status code should be 404
		And I should see a link to "/"

	Scenario: Clicking through to the homepage from a file not found result
		Given I am on a random path that doesn't exist
		And I click on the link to "/"
		Then I should be on the homepage 
