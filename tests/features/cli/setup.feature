Feature: The bin/setup.php script prepares the login
	In order to login to webfoo
	As an installer
	I must setup an account

	@cleanup_config
	Scenario: First time setup without required arguments
		Given there is no config file
		When I run the setup script with no arguments
		Then I should see "Usage: setup.php [OPTIONS]... USERNAME URL"
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
		When I run the setup script with arguments "user http://localhost/"
		Then I should see "setup.php: first time setup is already complete."
		And the exit status should be 1

	@cleanup_config
	Scenario: First time setup with too many arguments
		Given there is no config file
		When I run the setup script with arguments "user http://localhost/ user"
		Then I should see "Usage: setup.php [OPTIONS]... USERNAME URL"
		And I should see "Try 'setup.php --help' for more information."
		And the exit status should be 1

	@cleanup_config
	Scenario: First time setup without required URL argument
		Given there is no config file
		When I run the setup script with arguments "user"
		Then I should see "Usage: setup.php [OPTIONS]... USERNAME URL"
		And I should see "Try 'setup.php --help' for more information."
		And the exit status should be 1

	@cleanup_config
	Scenario Outline: First time setup with an invalid URL
		Given there is no config file
		When I run the setup script with arguments <args>
		Then I should see <error>
		And I should see "Try 'setup.php --help' for more information."
		And the exit status should be 1

		Examples:
		  | args                                     | error                                                                 |
		  | "user http:///example.com"               | "error: profile URL must be a URL"                                    |
		  | "user lemma"                             | "error: profile URL must be a URL"                                    |
		  | "user example.com/"                      | "error: profile URL must be a URL"                                    |
		  | "user gopher://example.com/"             | "error: profile URL must use HTTP or HTTPS"                           |
		  | "user foo://example.com/"                | "error: profile URL must use HTTP or HTTPS"                           |
		  | "user https://example.com/h/./"          | "error: profile URL must not include relative components in the path" |
		  | "user https://example.com/h/../"         | "error: profile URL must not include relative components in the path" |
		  | "user https://example.com/#i"            | "error: profile URL must not contain a fragment"                      |
		  | "user https://a@example.com/"            | "error: profile URL must not contain a username or password"          |
		  | "user https://:b@example.com/"           | "error: profile URL must not contain a username or password"          |
		  | "user https://a:b@example.com/"          | "error: profile URL must not contain a username or password"          |
		  | "user https://example.com:80/"           | "error: profile URL must not contain a port"                          |
		  | "user https://127.0.0.1/"                | "error: profile URL must not be an IPV4 address"                      |
		  | "user https://10.0.0.1/"                 | "error: profile URL must not be an IPV4 address"                      |
		  | "user https://[::1]/"                    | "error: profile URL must not be an IPV6 address"                      |
		  | "user https://[fdbf:67b7:26e1:7146::1]/" | "error: profile URL must not be an IPV6 address"                      |
		  | "user https://[]/"                       | "error: profile URL must be a URL"                                    |

	@cleanup_config
	Scenario Outline: First time setup with a valid URL
		Given there is no config file
		When I run the setup script with user "user" and URL <url>
		Then I should see "setup.php: Done! Enjoy WebFoo!"
		And the config file should have key "username" with value "user"
		And the config file should have key "me" with value <profile_url>
		And I should see a temporary password
		And the config file should contain the password hash
		And the exit status should be 0

		Examples:
		  | url                     | profile_url             |
		  | "http://localhost"      | "http://localhost/"     |
		  | "https://localhost"     | "https://localhost/"    |
		  | "http://localhost/"     | "http://localhost/"     |
		  | "http://localhost/?h"   | "http://localhost/?h"   |
		  | "http://localhost/?h="  | "http://localhost/?h="  |
		  | "http://localhost/?h=p" | "http://localhost/?h=p" |
		  | "http://localhost/u"    | "http://localhost/u"    |
		  | "http://localhost/u/"   | "http://localhost/u/"   |
		  | "http://localhost/u/e"  | "http://localhost/u/e"  |
		  | "http://localhost/u/e/" | "http://localhost/u/e/" |
		  | "https://private-dns"   | "https://private-dns/"  |

