<?php
require_once 'LOVDSeleniumBaseTestCase.php';

use \Facebook\WebDriver\WebDriverBy;
use \Facebook\WebDriver\WebDriverExpectedCondition;

class LoginAsManagerTest extends LOVDSeleniumWebdriverBaseTestCase
{
    public function testLoginAsManager()
    {
        $this->driver->get(ROOT_URL . "/src/logout");
        $this->driver->get(ROOT_URL . "/src/login");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/login$/', $this->driver->getCurrentURL()));
        $this->enterValue(WebDriverBy::name("username"), "manager");
        $this->enterValue(WebDriverBy::name("password"), "test1234");
        $element = $this->driver->findElement(WebDriverBy::xpath("//input[@value='Log in']"));
        $element->click();
        
    }
}
