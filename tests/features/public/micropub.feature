@mink:goutte
Feature: Managing content using third-party client applications with the Micropub method
	In order to manage my webfoo site
	As a micropub client user
	I must interact with my webfoo site using micropub

	Scenario: Discovery available to clients with HTTP link header
		Given I am on "/"
		Then there should be an HTTP "Link" header with value '</micropub/>; rel="micropub"'

	Scenario: Discovery available to clients with HTML link element
		Given I am on "/"
		Then there should be a link element with rel "micropub" and href "/micropub/"

	Scenario: Discovery with the IndieAuth\Client library
		Given I use the indieauth-client library
		When the client tries to discover the micropub endpoint
		Then the micropub endpoint is base_url plus "/micropub/"

	@user_exists
	Scenario: Receiving a micropub request with no matching token record
		Given I have not approved an authorization request
		And no tokens have been issued
		When I receive a micropub request
		Then the response status code should be 403
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request could not be matched to an authorized access token"

	Scenario: Receiving a micropub request via GET that is missing an access token
		Given I receive a micropub request via get that has no access token
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "unauthorized"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request did not provide an access token"

	Scenario: Receiving a micropub request via POST that is missing an access token
		Given I receive a micropub request via post that has no access token
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "unauthorized"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request did not provide an access token"

	Scenario: Receiving a micropub request that has both header and parameter access tokens
		Given I receive a micropub request that has both header and parameter access tokens
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the micropub request provided both header and parameter access tokens"

	@micropub_authorized
	Scenario: Responding to a configuration query
		Given I receive a configuration query
		Then the response status code should be 200
		And the response should be json
		And the json should have an "media-endpoint" parameter
		And the json "media-endpoint" parameter should be the empty string
		And the json should have an "syndicate-to" parameter
		And the json "syndicate-to" parameter should be the empty array

	@user_exists
	Scenario: Receiving a request to create a post without create scope
		Given I have approved an authorization request with scope parameter "update"
		And an access token has been issued
		When I receive a micropub request to create a post
		Then the response status code should be 401
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "insufficient_scope"
		And the json should have an "scope" parameter
		And the json "scope" parameter should be "create"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the access token must have 'create' scope to create a post"
		And there should not be a HTTP "Location" header

	@micropub_authorized
	Scenario: Receiving a request to create a post, that is missing the h parameter
		Given I receive a micropub request to create a post that has no h parameter
		Then the response status code should be 201
		And there should be a HTTP location header with the post permalink

	@micropub_authorized
	Scenario: Receiving a JSON-encoded request to create a post, that is missing the h parameter
		Given I have received a JSON-encoded micropub request to create:
		  """
		  {
			"properties": {
				"content": ["the content"]
			}
		  }
		  """
		Then the response status code should be 201
		And there should be a HTTP location header with the post permalink

	@micropub_authorized
	Scenario: Creating a new h-entry with many specified properties
		Given I have received a micropub request to create:
		  | parameter  | value                     |
		  | h          | entry                     |
		  | name       | the name                  |
		  | summary    | the summary               |
		  | content    | the content               |
		  | published  | 2020-01-01T00:00:00-04:00 |
		  | updated    | 2020-03-01T00:00:00-04:00 |
		  | category   | the category              |
		  | location   | geo:46.397180,-63.783277  |
		  | extension  | the extended property     |
		  | slug       | the-slug                  |
		Then the response status code should be 201
		And the post record should have yaml front matter
		And the yaml should have a "client_id" key with value "http://localhost/fake/"
		And the yaml should have a "media_type" key with value "text/plain"
		And the yaml should have a "slug" key with value "the-slug"
		And the yaml should have a nested array in "item" with a nested array in "type" with an element "h-entry"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "name" with an element "the name"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "published" with an element "2020-01-01T00:00:00-04:00"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "updated" with an element "2020-03-01T00:00:00-04:00"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "location" with an element "geo:46.397180,-63.783277"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "summary" with an element "the summary"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "the category"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "extension" with an element "the extended property"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "uid" with an element the post permalink
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "access_token"
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "content"
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "h"
		And the post record should have content following the front matter
		And the post record content should be "the content"

	@micropub_authorized
	Scenario: Creating a new h-entry via JSON-encoded post with many specified properties
		Given I have received a JSON-encoded micropub request to create:
		  """
		  {
			"type": ["h-entry"],
			"properties": {
				"name": ["the name"],
				"summary": ["the summary"],
				"content": ["the content"],
				"published": ["2020-01-01T00:00:00-04:00"],
				"updated": ["2020-03-01T00:00:00-04:00"],
				"category": ["the category"],
				"location": ["geo:46.397180,-63.783277"],
				"extension": ["the extended property"],
				"slug": ["the-slug"]
			}
		  }
		  """
		Then the response status code should be 201
		And the post record should have yaml front matter
		And the yaml should have a "client_id" key with value "http://localhost/fake/"
		And the yaml should have a "media_type" key with value "text/plain"
		And the yaml should have a "slug" key with value "the-slug"
		And the yaml should have a nested array in "item" with a nested array in "type" with an element "h-entry"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "name" with an element "the name"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "published" with an element "2020-01-01T00:00:00-04:00"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "updated" with an element "2020-03-01T00:00:00-04:00"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "location" with an element "geo:46.397180,-63.783277"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "summary" with an element "the summary"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "the category"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "extension" with an element "the extended property"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "uid" with an element the post permalink
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "access_token"
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "content"
		And the yaml nested array in "item" with a nested array in "properties" should not have a key "h"
		And the post record should have content following the front matter
		And the post record content should be "the content"

	@micropub_authorized
	Scenario: Visiting a post permalink after authoring it with micropub
		Given I create a new micropub post with content "cashew rope"
		When I visit the post permalink
		Then the response status code should be 200
		And the HTML should be valid
		And I should see "cashew rope"
		And I should not see "h-entry"

	@micropub_authorized
	Scenario: Authoring a micropub post with multiple categories
		Given I have received a micropub request to create:
		  | parameter  | value               |
		  | h          | entry               |
		  | content    | the content         |
		  | category[] | category the first  |
		  | category[] | category the second |
		  | category[] | category the third  |
		Then the post record should have yaml front matter
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the first"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the second"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the third"

	@micropub_authorized
	Scenario: Authoring a JSON-encoded micropub post with multiple categories
		Given I have received a JSON-encoded micropub request to create:
		  """
		  {
		    "type": ["h-entry"],
			"properties": {
				"content": ["the content"],
				"category": [
					"category the first",
					"category the second",
					"category the third"
				]
			}
		  }
		  """
		Then the post record should have yaml front matter
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the first"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the second"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "category" with an element "category the third"

	@micropub_authorized
	Scenario: Visiting the URL of a micropub post with multiple categories
		Given I have received a micropub request to create:
		  | parameter  | value               |
		  | h          | entry               |
		  | content    | the content         |
		  | category[] | category the first  |
		  | category[] | category the second |
		  | category[] | category the third  |
		When I visit the post permalink
		Then the response status code should be 200
		And I should see "the content"
		And I should see "category the first"
		And I should see "category the second"
		And I should see "category the third"

	@micropub_authorized
	Scenario: Authoring a micropub post with included photo specified as a URL
		Given I have received a micropub request to create:
		  | parameter  | value                                                                                                                                                   |
		  | h          | entry                                                                                                                                                   |
		  | content    | the content                                                                                                                                             |
		  | photo      | https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Moai_at_Rano_Raraku_%28Easter_Island%29.jpg/160px-Moai_at_Rano_Raraku_%28Easter_Island%29.jpg |
		Then the post record should have yaml front matter
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "photo" with an element "https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Moai_at_Rano_Raraku_%28Easter_Island%29.jpg/160px-Moai_at_Rano_Raraku_%28Easter_Island%29.jpg"

	@micropub_authorized
	Scenario: Authoring a micropub post with included photo provided as binary data
		Given I have received a micropub request with embedded media to create:
		  | parameter  | value              |
		  | h          | entry              |
		  | content    | the content        |
		  | photo      | from_file: 0-1.jpg |
		Then the post record should have yaml front matter
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "photo" with an element that ends with "media/photo1.jpg"

	@micropub_authorized
	Scenario: Viewing a photo that has been published via micropub post with included photo provided as binary data
		Given I have received a micropub request with embedded photo
		When I visit the photo url
		Then the response status code should be 200
		And there should be an HTTP "Content-Type" header with value "image/jpeg"

	@micropub_authorized
	Scenario: Authoring a micropub post with multiple included photos provided as binary data
		Given I have received a micropub request with embedded media to create:
		  | parameter  | value              |
		  | h          | entry              |
		  | content    | the content        |
		  | photo[]    | from_file: 0-1.jpg |
		  | photo[]    | from_file: 1-0.gif |
		  | photo[]    | from_file: 1-1.png |
		Then the post record should have yaml front matter
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "photo" with an element that ends with "media/photo1.jpg"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "photo" with an element that ends with "media/photo2.gif"
		And the yaml should have a nested array in "item" with a nested array in "properties" with a nested array in "photo" with an element that ends with "media/photo3.png"

	@micropub_authorized
	Scenario: Responding to a source content query that is missing the URL parameter
		Given I create a new micropub post with content "cashew rope"
		When I receive a source query that is missing the URL parameter
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "the source content query must include a post URL"

	@micropub_authorized
	Scenario Outline: Responding to a source content query that does not match any content URL
		Given I create a new micropub post with content "cashew rope"
		When I receive a source query for <url>
		Then the response status code should be 400
		And the response should be json
		And the json should have an "error" parameter
		And the json "error" parameter should be "invalid_request"
		And the json should have an "error_description" parameter
		And the json "error_description" parameter should be "there is no post at the requested URL"

		Examples:
		  | url                              |
		  | "http://localhost"               |
		  | "http://localhost/"              |
		  | "http://localhost/DNE/"          |
		  | "http://localhost/1901/01/01/1/" |

	@micropub_authorized
	Scenario: Responding to a source content query
		Given I have received a micropub request to create:
		  | parameter  | value                |
		  | h          | entry                |
		  | content    | the content          |
		  | published  | 2019-04-30T20:16:04Z |
		When I receive a source query for the post
		Then the response status code should be 200
		And the response should be json
		And the json should have an "type" parameter
		And the json "type" parameter should be an array with an element "h-entry"
		And the json should have an "properties" parameter
		And the json "properties" parameter should have a nested array in "content" with an element "the content"
		And the json "properties" parameter should have a nested array in "published" with an element "2019-04-30T20:16:04+00:00"

	@micropub_authorized
	Scenario: Responding to a source content query that includes a property list
		Given I have received a micropub request to create:
		  | parameter  | value              |
		  | h          | entry              |
		  | content    | the content        |
		  | category[] | example the first  |
		  | category[] | example the second |
		  | extension  | the extension      |
		When I receive a source query for the post properties "content extension"
		Then the response status code should be 200
		And the response should be json
		And the json should not have an "type" parameter
		And the json should have an "properties" parameter
		And the json "properties" parameter should have a nested array in "content" with an element "the content"
		And the json "properties" parameter should have a nested array in "extension" with an element "the extension"
		And the json "properties" parameter should not have an "published" key
		And the json "properties" parameter should not have an "category" key

	@micropub_authorized
	Scenario: Authoring a micropub post with HTML content
		Given I have received a JSON-encoded micropub request to create:
		  """
		  {
		    "type": ["h-entry"],
			"properties": {
				"content": [
					{
						"html": "<strong>this text within a strong element</strong>"
					}
				]
			}
		  }
		  """
		Then the post record should have yaml front matter
		And the yaml should have a "media_type" key with value "text/html"

	@micropub_authorized
	Scenario: Visiting the URL of a micropub post with HTML content
		Given I have received a JSON-encoded micropub request to create:
		  """
		  {
		    "type": ["h-entry"],
			"properties": {
				"content": [
					{
						"html": "<strong>this text within a strong element</strong>"
					}
				]
			}
		  }
		  """
		When I visit the post permalink
		Then the response status code should be 200
		And the HTML should be valid
		And I should see "this text within a strong element"
		And I should not see "<strong>"
		And there should be a "strong" element with text content "this text within a strong element"
