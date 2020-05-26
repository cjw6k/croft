Feature: The bin/setup.php script prepares the login
	In order to login to webfoo
	As an installer
	I must setup an account

	Scenario: First time setup without required arguments
		Given there is no config file
		When I run the setup script with no arguments
		Then I should see "Usage: setup.php [OPTIONS]... USERNAME"
		And I should see "Try 'setup.php --help' for more information."
		And the exit status should be 1

	@make_config @cleanup_config
	Scenario: Re-running setup without required arguments
		Given there is a config file
		When I run the setup script with no arguments
		Then I should see "setup.php: first time setup is already complete."
		And the exit status should be 1

	@make_config @cleanup_config
	Scenario: Re-running setup with required arguments
		Given there is a config file
		When I run the setup script with arguments "user"
		Then I should see "setup.php: first time setup is already complete."
		And the exit status should be 1

	Scenario: First time setup with too many arguments
		Given there is no config file
		When I run the setup script with arguments "user user"
		Then I should see "Usage: setup.php [OPTIONS]... USERNAME"
		And I should see "Try 'setup.php --help' for more information."
		And the exit status should be 1

	@cleanup_config
	Scenario: First time setup with username argument
		Given there is no config file
		When I run the setup script with arguments "user"
		Then I should see "setup.php: Done! Enjoy WebFoo!"
		And the config file should have key "username" with value "user"
		And I should see a temporary password
		And the config file should contain the password hash
		And the exit status should be 0
