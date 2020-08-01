<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class PrivateContext extends MinkContext implements Context, SnippetAcceptingContext
{
	use WebContextTrait;

	/**
	 * @AfterScenario @cleanup_async
	 */
	public function cleanupAsync()
	{
		if(file_exists(VAR_ROOT . 'async/.async-last')){
			unlink(VAR_ROOT . 'async/.async-last');
		}

		if(file_exists(VAR_ROOT . 'async/.async-active')){
			unlink(VAR_ROOT . 'async/.async-active');
		}
	}

    /**
     * @Given No async operations are currently running
     */
    public function noAsyncOperationsAreCurrentlyRunning()
    {
        assertFileNotExists(VAR_ROOT . 'async/.async-active');
    }

    /**
     * @Given No async operation has run on this install
     */
    public function noAsyncOperationHasRunOnThisInstall()
    {
		if(file_exists(VAR_ROOT . 'async/.async-last')){
			unlink(VAR_ROOT . 'async/.async-last');
		}
        assertFileNotExists(VAR_ROOT . 'async/.async-last');
    }

    /**
     * @When I visit the homepage
     */
    public function iVisitTheHomepage()
    {
        $this->getSession()->visit('/');
    }

    /**
     * @Then there should be a marker of the last async operation start time
     */
    public function thereShouldBeAMarkerOfTheLastAsyncOperationStartTime()
    {
        assertFileExists(VAR_ROOT . 'async/.async-last');
		$this->_last_async = file_get_contents(VAR_ROOT . 'async/.async-last');
    }

    /**
     * @Then there should be no async operations currently running
     */
    public function thereShouldBeNoAsyncOperationsCurrentlyRunning()
    {
        $this->noAsyncOperationsAreCurrentlyRunning();
    }

    /**
     * @Given An async operation is currently running
     */
    public function anAsyncOperationIsCurrentlyRunning()
    {
        assertFileNotExists(VAR_ROOT . 'async/.async-active');
		touch(VAR_ROOT . 'async/.async-active');
		chmod(VAR_ROOT . 'async/.async-active', 0777);
		if(file_exists(VAR_ROOT . 'async/.async-last')){
			unlink(VAR_ROOT . 'async/.async-last');
		}
		$this->_last_async = time();
		file_put_contents(VAR_ROOT . 'async/.async-last', $this->_last_async);
		chmod(VAR_ROOT . 'async/.async-last', 0777);
    }

    /**
     * @Then the last async operation start time should not be changed
     */
    public function theLastAsyncOperationStartTimeShouldNotBeChanged()
    {
        assertFileExists(VAR_ROOT . 'async/.async-last');
		assertEquals($this->_last_async, file_get_contents(VAR_ROOT . 'async/.async-last'));
    }

    /**
     * @Given the last async operation started :arg1 seconds ago
     */
    public function theLastAsyncOperationStartedSecondsAgo($arg1)
    {
		$this->_last_async = time() - $arg1;
        file_put_contents(VAR_ROOT . 'async/.async-last', $this->_last_async);
		chmod(VAR_ROOT . 'async/.async-last', 0777);
    }

    /**
     * @Then the last async operation start time should be changed
     */
    public function theLastAsyncOperationStartTimeShouldBeChanged()
    {
        assertFileExists(VAR_ROOT . 'async/.async-last');
		assertNotEquals($this->_last_async, file_get_contents(VAR_ROOT . 'async/.async-last'));
    }
}
