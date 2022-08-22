<?php

namespace Tests\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Croft\From;

use function file_exists;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertNotEquals;
use function unlink;
use function file_get_contents;
use function touch;
use function chmod;
use function time;
use function file_put_contents;

/**
 * Defines application features from the specific context.
 */
class NotPublic extends MinkContext implements Context, SnippetAcceptingContext
{
    use Web;

    /** @AfterScenario @cleanup_async */
    public function cleanupAsync(): void
    {
        if (file_exists(From::VAR->dir() . 'async/.async-last')) {
            unlink(From::VAR->dir() . 'async/.async-last');
        }

        if (! file_exists(From::VAR->dir() . 'async/.async-active')) {
            return;
        }

        unlink(From::VAR->dir() . 'async/.async-active');
    }

    /** @Given No async operations are currently running */
    public function noAsyncOperationsAreCurrentlyRunning(): void
    {
        assertFileDoesNotExist(From::VAR->dir() . 'async/.async-active');
    }

    /** @Given No async operation has run on this install */
    public function noAsyncOperationHasRunOnThisInstall(): void
    {
        if (file_exists(From::VAR->dir() . 'async/.async-last')) {
            unlink(From::VAR->dir() . 'async/.async-last');
        }

        assertFileDoesNotExist(From::VAR->dir() . 'async/.async-last');
    }

    /** @When I visit the homepage */
    public function iVisitTheHomepage(): void
    {
        $this->getSession()->visit('/');
    }

    /** @Then there should be a marker of the last async operation start time */
    public function thereShouldBeAMarkerOfTheLastAsyncOperationStartTime(): void
    {
        assertFileExists(From::VAR->dir() . 'async/.async-last');
        $this->_last_async = file_get_contents(From::VAR->dir() . 'async/.async-last');
    }

    /** @Then there should be no async operations currently running */
    public function thereShouldBeNoAsyncOperationsCurrentlyRunning(): void
    {
        $this->noAsyncOperationsAreCurrentlyRunning();
    }

    /** @Given An async operation is currently running */
    public function anAsyncOperationIsCurrentlyRunning(): void
    {
        assertFileDoesNotExist(From::VAR->dir() . 'async/.async-active');
        touch(From::VAR->dir() . 'async/.async-active');
        chmod(From::VAR->dir() . 'async/.async-active', 0777);

        if (file_exists(From::VAR->dir() . 'async/.async-last')) {
            unlink(From::VAR->dir() . 'async/.async-last');
        }

        $this->_last_async = time();
        file_put_contents(From::VAR->dir() . 'async/.async-last', $this->_last_async);
        chmod(From::VAR->dir() . 'async/.async-last', 0777);
    }

    /** @Then the last async operation start time should not be changed */
    public function theLastAsyncOperationStartTimeShouldNotBeChanged(): void
    {
        assertFileExists(From::VAR->dir() . 'async/.async-last');
        assertEquals($this->_last_async, file_get_contents(From::VAR->dir() . 'async/.async-last'));
    }

    /** @Given the last async operation started :arg1 seconds ago */
    public function theLastAsyncOperationStartedSecondsAgo(string $arg1): void
    {
        $this->_last_async = time() - $arg1;
        file_put_contents(From::VAR->dir() . 'async/.async-last', $this->_last_async);
        chmod(From::VAR->dir() . 'async/.async-last', 0777);
    }

    /** @Then the last async operation start time should be changed */
    public function theLastAsyncOperationStartTimeShouldBeChanged(): void
    {
        assertFileExists(From::VAR->dir() . 'async/.async-last');
        assertNotEquals($this->_last_async, file_get_contents(From::VAR->dir() . 'async/.async-last'));
    }
}
