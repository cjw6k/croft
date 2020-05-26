@user_exists @mink:goutte
Feature: A logged in user may log out to end the log in session
	In order to log out of the admin area
	As a logged in user
	I must log out

	Background:
        Given I am on "/login/"
		And I log in with username "test" and password "test"	
	
	Scenario: Clicking the log out button
		Given I am on "/"
		When I click on the link to "/logout/"
		Then I should be on "/"
		And I should not see "Log Out"
