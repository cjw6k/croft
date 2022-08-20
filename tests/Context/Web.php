<?php

namespace Tests\Context;

use Croft\From;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertArrayNotHasKey;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertIsObject;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertObjectHasAttribute;
use function PHPUnit\Framework\assertObjectNotHasAttribute;
use function PHPUnit\Framework\assertTrue;
use function yaml_emit_file;
use function password_hash;
use function unlink;
use function json_decode;
use function var_dump;
use function sprintf;
use function substr;
use function md5;
use function mt_rand;
use function explode;
use function trim;
use function rtrim;
use function sleep;

use const PASSWORD_DEFAULT;
use const PHP_EOL;

/**
 * Defines application features from the specific context
 */
trait Web
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(string $base_url = 'http://127.0.0.1')
    {
        $this->base_url = $base_url;
    }

    /** @BeforeScenario @user_exists */
    public function makeConfigWithUsers(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file already exists');
        yaml_emit_file(
            From::___->dir() . 'config.yml',
            [
                'title' => 'WebFoo',
                'username' => 'test',
                'password' => password_hash('test', PASSWORD_DEFAULT),
                'me' => 'http://localhost/',
            ]
        );
    }

    /** @BeforeScenario @config_indieauth_exceptions */
    public function makeConfigWithUsersAndIndieAuthExceptions(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file already exists');
        yaml_emit_file(
            From::___->dir() . 'config.yml',
            [
                'title' => 'WebFoo',
                'username' => 'test',
                'password' => password_hash('test', PASSWORD_DEFAULT), 'me' => 'http://localhost/',
                'indieauth' => [
                    'exceptions' => [
                        'client_id' => [
                            'missing_path_component' => [
                                'omnibear.com',
                                'indigenous.realize.be',
                                'indiebookclub.biz',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /** @BeforeScenario @config_micropub_exceptions */
    public function makeConfigWithUsersAndMicropubExceptions(): void
    {
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file already exists');
        yaml_emit_file(
            From::___->dir() . 'config.yml',
            [
                'title' => 'WebFoo',
                'username' => 'test',
                'password' => password_hash('test', PASSWORD_DEFAULT), 'me' => 'http://localhost/',
                'micropub' => [
                    'exceptions' => [
                        'two_copies_of_access_token' => [
                            'micropub.rocks',
                        ],
                    ],
                ],
            ]
        );
    }

    /** @AfterScenario */
    public function resetSession(): void
    {
        $this->getSession()->reset();
    }

    /**
     * @AfterScenario @user_exists
     * @AfterScenario @config_indieauth_exceptions
     * @AfterScenario @config_micropub_exceptions
     */
    public function removeConfig(): void
    {
        unlink(From::___->dir() . 'config.yml');
        assertFileDoesNotExist(From::___->dir() . 'config.yml', 'config.yml file exists');
    }

    /** @Then show me */
    public function showMe(): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);

        if ($json) {
            var_dump($json);

            return;
        }

        echo $content, PHP_EOL;
    }

    /** @Given I am on a random path that doesn't exist */
    public function iAmOnARandomPathThatDoesntExist(): void
    {
        $this->getSession()->visit(
            $this->base_url . sprintf(
                '%s/%s/%s/%s',
                $this->randomString(),
                $this->randomString(),
                $this->randomString(),
                $this->randomString()
            )
        );
    }

    private function randomString(): string
    {
        return substr(md5(mt_rand()), 0, 7);
    }

    /** @Then I should see a link to :href */
    public function iShouldSeeALinkTo(string $href): void
    {
        assertNotNull($this->getSession()->getPage()->find('xpath', '//a[@href="' . $href . '"]'));
    }

    /** @Given I click on the link to :href */
    public function iClickOnTheLinkTo(string $href): void
    {
        $link = $this->getSession()->getPage()->find('xpath', '//a[@href="' . $href . '"]');
        assertNotNull($link);
        $link->click();
    }

    /** @Then the HTML should be valid */
    public function theHTMLShouldBeValid(): void
    {
        echo 'html validation disabled; do not use external validation resource';
    }

    /** @Given I log in with username :username and password :password */
    public function iLogInWithEmailAndPassword2(string $username, string $password): void
    {
        $this->doLogIn($username, $password);
    }

    public function doLogIn(string $username, string $password): void
    {
        $this->getSession()->visit('/login/');
        $page = $this->getSession()->getPage();
        $page->find('named', ['field', 'username'])->setValue($username);
        $page->find('named', ['field', 'userkey'])->setValue($password);
        $page->find('css', 'button[type=submit]')->submit();
    }

    /** @Then there should be an HTTP :arg1 header with value :arg2 */
    public function thereShouldBeAnHttpHeaderWithValue(string $arg1, string $arg2): void
    {
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey($arg1, $headers);

        $found = false;

        foreach (explode(',', $headers[$arg1][0]) as $header) {
            if (trim($header) != $arg2) {
                continue;
            }

            $found = true;
        }

        assertTrue($found);
    }

    /** @Then there should not be a HTTP :arg1 header */
    public function thereShouldNotBeAHttpHeader(string $arg1): void
    {
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayNotHasKey($arg1, $headers);
    }

    /** @Then there should be a link element with rel :arg1 and href :arg2 */
    public function thereShouldBeALinkElementWithRelAndHref(string $arg1, string $arg2): void
    {
        $link = $this->getSession()->getPage()->find('xpath', "//link[@rel='$arg1']");
        assertNotNull($link);
        assertEquals($arg2, $link->getAttribute('href'));
    }

    /** @Given I am logged in */
    public function iAmLoggedIn(): void
    {
        $this->doLogin("test", "test");
    }

    /** @When I login */
    public function iLogin(): void
    {
        $page = $this->getSession()->getPage();
        $page->find('named', ['field', 'username'])->setValue("test");
        $page->find('named', ['field', 'userkey'])->setValue("test");
        $page->find('css', 'button[type=submit]')->submit();
    }

    /** @Then the response should be json */
    public function theResponseShouldBeJson(): void
    {
        $headers = $this->getSession()->getResponseHeaders();
        assertArrayHasKey('Content-Type', $headers);
        assertEquals($headers['Content-Type'][0], 'application/json; charset=UTF-8');
    }

    /**
     * @Then the json should have an :arg1 parameter
     * @Then the json should have a :arg1 parameter
     */
    public function theJsonShouldHaveAnParameter(string $arg1): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertNotNull($json);
        assertObjectHasAttribute($arg1, $json);
    }

    /**
     * @Then the json should not have an :arg1 parameter
     * @Then the json should not have a :arg1 parameter
     */
    public function theJsonShouldNotHaveAnParameter(string $arg1): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertNotNull($json);
        assertObjectNotHasAttribute($arg1, $json);
    }

    /** @Then the json :arg1 parameter should be :arg2 */
    public function theJsonParameterShouldBe(string $arg1, string $arg2): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertEquals($json->$arg1, $arg2);
    }

    /** @Then the :arg1 checkbox with value :arg2 should be checked */
    public function theCheckboxWithValueShouldBeChecked(string $arg1, string $arg2): void
    {
        $checkbox = $this->getSession()->getPage()->find(
            'xpath',
            '//input[@type="checkbox" and @name="' . $arg1 . '" and @value="' . $arg2 . '"]'
        );
        assertTrue($checkbox->isChecked());
    }

    /** @When I uncheck the :arg1 checkbox with value :arg2 */
    public function iUncheckTheCheckboxWithValue(string $arg1, string $arg2): void
    {
        $checkbox = $this->getSession()->getPage()->find(
            'xpath',
            '//input[@type="checkbox" and @name="' . $arg1 . '" and @value="' . $arg2 . '"]'
        );
        $checkbox->uncheck();
    }

    /** @Then the json :arg1 parameter should be base_url plus :arg2 */
    public function theJsonParameterShouldBeBaseUrlPlus(string $arg1, string $arg2): void
    {
        $this->theJsonParameterShouldBe($arg1, rtrim($this->base_url, '/') . $arg2);
    }

    /** @Then the json :arg1 parameter should be the empty string */
    public function theJsonParameterShouldBeTheEmptyString(string $arg1): void
    {
        $this->theJsonParameterShouldBe($arg1, '');
    }

    /** @Then the json :arg1 parameter should be the empty array */
    public function theJsonParameterShouldBeTheEmptyArray(string $arg1): void
    {
        $this->theJsonParameterShouldBe($arg1, []);
    }

    /** @Then the json :arg1 parameter should be an array with an element :arg2 */
    public function theJsonParameterShouldBeAnArrayWithAnElement(string $arg1, string $arg2): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertNotNull($json);
        assertObjectHasAttribute($arg1, $json);
        assertIsArray($json->$arg1);
        assertContains($arg2, $json->$arg1);
    }

    /** @Then the json :arg1 parameter should have a nested array in :arg2 with an element :arg3 */
    public function theJsonParameterShouldHaveANestedArrayInWithAnElement(
        string $arg1,
        string $arg2,
        string $arg3
    ): void {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertNotNull($json);
        assertObjectHasAttribute($arg1, $json);
        assertIsObject($json->$arg1);
        assertObjectHasAttribute($arg2, $json->$arg1);
        assertIsArray($json->$arg1->$arg2);
        assertContains($arg3, $json->$arg1->$arg2);
    }

    /** @Then the json :arg1 parameter should not have an :arg2 key */
    public function theJsonParameterShouldNotHaveAnKey(string $arg1, string $arg2): void
    {
        $content = $this->getSession()->getPage()->getContent();
        $json = json_decode($content);
        assertNotNull($json);
        assertObjectHasAttribute($arg1, $json);
        assertObjectNotHasAttribute($arg2, $json->$arg1);
    }

    /** @Then the response body should be :arg1 */
    public function theResponseBodyShouldBe(string $arg1): void
    {
        assertEquals($arg1, $this->getSession()->getPage()->getContent());
    }

    /** @Then the response body should be the empty string */
    public function theResponseBodyShouldBeTheEmptyString(): void
    {
        assertEmpty($this->getSession()->getPage()->getContent());
    }

    /** @When I visit :arg1 */
    public function iVisit(string $arg1): void
    {
        $this->getSession()->visit($arg1);
    }

    /** @When I wait :arg1 seconds */
    public function iWaitSeconds(int $arg1): void
    {
        sleep($arg1);
    }

    /** @Given I make a POST request to the home page */
    public function iMakeAPostRequestToTheHomePage(): void
    {
        $this->getSession()->getDriver()->getClient()->request('POST', '/');
    }
}
