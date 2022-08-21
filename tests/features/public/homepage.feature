Feature: The homepage is where everything begins
	In order to visit the website
	As a visitor
	I must visit the landing page

	Scenario: Visiting the landing page
		Given I am on the homepage
		Then the response status code should be 200
		And the HTML should be valid
