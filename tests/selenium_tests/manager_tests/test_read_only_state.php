<?php
require_once 'LOVDSeleniumBaseTestCase.php';

use \Facebook\WebDriver\WebDriverBy;
use \Facebook\WebDriver\WebDriverExpectedCondition;

class TestReadOnlyState extends LOVDSeleniumWebdriverBaseTestCase
{
    public function testTestReadOnlyState ()
    {
        // Test that LOVD is in the read-only state.

        // First, log out of any session that might exist at this time.
        $this->logout();

        // There should be no link to register yourself.
        // First, I had this findElements(), but Chrome doesn't like that at all, and times out.
        // Firefox anyway took quite some time, because of the timeout that we have set if elements are not found immediately (normally needed if pages load slowly).
        // $this->assertFalse((bool) count($this->driver->findElements(WebDriverBy::xpath('//a/b[text()="Register as submitter"]'))));
        // New attempt to test for absence of register link.
        $this->assertFalse(strpos($this->driver->findElement(WebDriverBy::xpath('//table[@class="logo"]//td[3]'))->getText(), 'Register as submitter'));

        $this->driver->get(ROOT_URL . '/src/login');
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/login$/', $this->driver->getCurrentURL()));

        // Verify warning exists. Finds any TD with this text.
        $this->driver->findElement(WebDriverBy::xpath('//td[text()="This installation is currently configured to be read-only. Only Managers and higher level users can log in."]'));

        // Attempt to log in, should fail in a specific way.
        $this->enterValue(WebDriverBy::name('username'), 'submitter');
        $this->enterValue(WebDriverBy::name('password'), 'test1234');
        $element = $this->driver->findElement(WebDriverBy::xpath('//input[@value="Log in"]'));
        $element->click();

        // Should return a proper error message.
        $this->assertEquals('This installation is currently configured to be read-only. Your user level is not sufficient to log in.',
            $this->driver->findElement(WebDriverBy::cssSelector('div[class=err]'))->getText());

        // Also curators should fail.
        $this->enterValue(WebDriverBy::name('username'), 'curator');
        $this->enterValue(WebDriverBy::name('password'), 'test1234');
        $element = $this->driver->findElement(WebDriverBy::xpath('//input[@value="Log in"]'));
        $element->click();

        // Should return a proper error message.
        $this->assertEquals('This installation is currently configured to be read-only. Your user level is not sufficient to log in.',
            $this->driver->findElement(WebDriverBy::cssSelector('div[class=err]'))->getText());
    }
}
