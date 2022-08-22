Feature: Sending and received webmentions
	In order to use notify and be notified when blogs mention each other
	As a webfoo site owner
	I must send and receive webmentions

	Scenario: Discovery available to clients with HTTP link header
		Given I am on "/"
		Then there should be an HTTP "Link" header with value '</webmention/>; rel="webmention"'

	Scenario: Discovery available to clients with HTML link element
		Given I am on "/"
		Then there should be a link element with rel "webmention" and href "/webmention/"

	Scenario: Discovery with the IndieWeb\mention-client library
		Given I use the mention-client library
		When the client tries to discover the webmention endpoint
		Then the webmention endpoint is base_url plus "/webmention/"

	Scenario: Receiving a webmention with no target parameter
		Given I receive a webmention that has no target parameter
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: target parameter is required"

	Scenario: Receiving a webmention with no source parameter
		Given I receive a webmention that has no source parameter
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: source parameter is required"

	Scenario: Receiving a webmention with empty target parameter
		Given I receive a webmention that has an empty target parameter
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: target parameter must not be empty"

	Scenario: Receiving a webmention with empty source parameter
		Given I receive a webmention that has an empty source parameter
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: source parameter must not be empty"

	Scenario Outline: Receiving a webmention with target set to an invalid URL
		Given I receive a webmention that has a target parameter <target>
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the target URL is invalid"

		Examples:
		  | target                  |
		  | "http:///example.com"   |
		  | "cashew rope"           |
		  | "example.com/"          |
		  | "gopher://example.com/" |
		  | "foo://example.com/"    |

	@user_exists
	Scenario Outline: Receiving a webmention with target set to the wrong domain
		Given I receive a webmention that has a target parameter <target>
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the target URL is not valid at this domain"

		Examples:
		  | target                  |
		  | "http://example.com/"   |
		  | "http://example.net/"   |
		  | "http://local-dns/"     |

	@user_exists
	Scenario Outline: Receiving a webmention with target set to a local path that DNE
		Given I receive a webmention that has a target parameter base_url plus <target>
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the target URL is for content that does not exist here"

		Examples:
		  | target                                                                                                                                             |
		  | "cashew-rope/"                                                                                                                                     |
		  | "1879/03/14/1/"                                                                                                                                    |
		  | "1905/06/09/1/ueber-einen-die-erzeugung-und-verwandlung-des-lichtes-betreffenden-heuristischen-gesichtspunkt"                                      |
		  | "1905/07/18/1/ueber-die-von-der-molekularkinetischen-theorie-der-waerme-geforderte-bewegung-von-in-ruhenden-fluessigkeiten-suspendierten-teilchen" |
		  | "1905/09/26/1/zur-elektrodynamik-bewegter-koerper"                                                                                                 |
		  | "1905/11/21/1/ist-die-traegheit-eines-koerpers-von-seinem-energieinhalt-abhaengig"                                                                 |

	Scenario Outline: Receiving a webmention with source set to an invalid URL
		Given I receive a webmention that has a source parameter <source>
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the source URL is invalid"

		Examples:
		  | source                  |
		  | "http:///example.com"   |
		  | "cashew rope"           |
		  | "example.com/"          |
		  | "gopher://example.com/" |
		  | "foo://example.com/"    |

	Scenario Outline: Receiving a webmention with target set to a reserved path
		Given I receive a webmention that has a target parameter base_url plus <target>
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the target URL does not accept webmentions"

		Examples:
		  | target                          |
		  | "auth/"                         |
		  | "token/"                        |
		  | "micropub/"                     |
		  | "login/"                        |
		  | "logout/"                       |
		  | "webmention/"                   |
		  | "2020/07/04/1/media/photo1.jpg" |

	Scenario: Receiving a webmention with target and source set to the same url
		Given I receive a webmention that has target parameter "http://localhost/" and source parameter "http://localhost/"
		Then the response status code should be 400
		And there should be an HTTP "Content-Type" header with value "text/plain; charset=UTF-8"
		And the response body should be "Error: the target URL and source URL must not be the same"

	@micropub_authorized
	Scenario: Receiving a webmention from my own site
		Given I have created a new post with content "cashew rope"
		And I have no incoming webmentions spooled for verification
		And an async operation will not start on the next visit
		When I receive a webmention from my own site that has the post permalink in the target
		Then the response status code should be 202
		And the response body should be the empty string
		And there should be an incoming webmention spooled for verification

	@micropub_authorized
	Scenario: Receiving a webmention from another site
		Given I have created a new post with content "cashew rope"
		And I have no incoming webmentions spooled for verification
		And an async operation will not start on the next visit
		When I receive a webmention from "http://example.com/fake/" that has the post permalink in the target
		Then the response status code should be 202
		And the response body should be the empty string
		And there should be an incoming webmention spooled for verification

	@micropub_authorized
	Scenario: Processing the incoming webmention spool
		Given I have an incoming webmention spooled for verification
		And an async operation will start on the next visit
		When I visit "/"
		And I wait 2 seconds
		Then I should have no incoming webmentions spooled for verification

	@micropub_authorized
	Scenario: Processing the incoming webmention spool; target has been deleted after spooling
		Given I have an incoming webmention spooled for verification with target base_url plus "2020/07/03/1/"
		And an async operation will start on the next visit
		When I visit "/"
		And I wait 2 seconds
		Then I should have no incoming webmentions spooled for verification

	@micropub_authorized
	Scenario: Processing the incoming webmention spool; source is 404
		Given I have created a new post with content "cashew rope"
		And I have an incoming webmention spooled for verification with target the post permalink and source "http://localhost/fake/"
		And an async operation will start on the next visit
		When I visit "/"
		And I wait 2 seconds
		Then I should have no incoming webmentions spooled for verification
		And the post record should have no webmentions

	@micropub_authorized
	Scenario: Processing the incoming webmention spool; source does not include a link to target
		Given I have created a new post with content "cashew rope"
		And I have an incoming webmention spooled for verification with target the post permalink and source "http://localhost/"
		And an async operation will start on the next visit
		When I visit "/"
		And I wait 2 seconds
		Then I should have no incoming webmentions spooled for verification
		And the post record should have no webmentions

	@micropub_authorized
	Scenario: Processing the incoming webmention spool; source includes a link to target
		Given I have created a new post with content "cashew rope"
		And I have created a webmention source post with content the post permalink
		And I have an incoming webmention spooled for verification with target the post permalink and source the webmention source post permalink
		And an async operation will start on the next visit
		When I visit "/"
		And I wait 2 seconds
		Then I should have no incoming webmentions spooled for verification
		And the post record should have webmentions
		And there should be 1 generic webmentions
		And the list of generic webmentions should contain the webmention source post permalink
		And there should be 0 repost webmentions
		And there should be 0 response webmentions
		And there should be 0 like webmentions
		And there should be 0 bookmark webmentions
		And there should be 0 reply webmentions
