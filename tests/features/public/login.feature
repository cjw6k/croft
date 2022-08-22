Feature: The login page provides web-based admin controls to the site owner
	In order to manage content in webfoo
	As a site owner
	I need to login

	Scenario: Visiting the login page
		Given I am on "/login/"
		Then the response status code should be 200
		And the HTML should be valid
		And I should see a link to "/"
		And I should see "Username"
		And the "username" field should contain ""
		And I should see "Password"
		And the "userkey" field should contain ""
		And I should see "Log In"

	Scenario: Clicking through to the homepage from the login page
		Given I am on "/login/"
		When I click on the link to "/"
		Then I should be on the homepage

	Scenario: Logging in with blank form
		Given I am on "/login/"
		When I press "Log In"
		Then I should be on "/login/"
		And I should see "Log In Error"
		And I should see "Username is required"
		And I should see "Password is required"

	Scenario: Logging in with missing password
		Given I am on "/login/"
		When I fill in "username" with "test"
		And I press "Log In"
		Then I should be on "/login/"
		And I should see "Log In Error"
		And I should see "Password is required"
		And I should not see "Username is required"

	Scenario: Logging in with missing username
		Given I am on "/login/"
		When I fill in "userkey" with "test"
		And I press "Log In"
		Then I should be on "/login/"
		And I should see "Log In Error"
		And I should see "Username is required"
		And I should not see "Password is required"

	Scenario: Logging in with invalid account
		Given I am on "/login/"
		When I fill in "username" with "test"
		And I fill in "userkey" with "test"
		And I press "Log In"
		Then I should be on "/login/"
		And I should see "Log In Error"
		And I should see "The username and password entered did not match the config. Please double-check and try again"

	@user_exists
	Scenario: Logging in with valid account
		Given I am on "/login/"
		When I fill in "username" with "test"
		And I fill in "userkey" with "test"
		And I press "Log In"
		Then I should be on "/"
		And I should see "Log Out"
