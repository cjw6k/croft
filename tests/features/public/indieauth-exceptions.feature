@mink:goutte
Feature: WebFoo permits departure from the IndieAuth spec for exceptional clients specified in the config file
	In order to permit inaccurate implementations of the spec
	As a webfoo site owner
	I may provide exceptional implementations in an allowlist

	@config_indieauth_exceptions
	Scenario Outline: Permit missing path violations for specific client_ids
		Given I am logged in
		And I receive an authorization request with client_id <client> and redirect_uri <redirect>
		Then I should see "Continue"

		Examples:
		  | client                          | redirect                         |
		  | "https://omnibear.com"          | "https://omnibear.com/"          |
		  | "https://indigenous.realize.be" | "https://indigenous.realize.be/" |
		  | "https://indiebookclub.biz"     | "https://indiebookclub.biz"      |

	@config_indieauth_exceptions
	Scenario Outline: Permit no other violations of the spec for the specific client_ids
		Given I am logged in
		And I receive an authorization request with client_id <client> and redirect_uri <redirect>
		Then I should not see "Continue"

		Examples:
		  | client                          | redirect                        |
		  | "https://omnibear.com"          | "https://localhost/fake/"       |
		  | "https://indigenous.realize.be" | "http://indigenous.realize.be/" |
		  | "https://indiebookclub.biz"     | "https://indiebookclub.biz:70/" |
