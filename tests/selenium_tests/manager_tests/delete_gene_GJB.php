<?php
class Example extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("https://localhost/svn/LOVD3/trunk/src/install/");
  }

  public function testMyTestCase()
  {
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/phenotypes\/0000000002$/',$this->getLocation()));
    $this->click("id=tab_genes");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/genes\/GJB1$/',$this->getLocation()));
    $this->click("id=viewentryOptionsButton_Genes");
    $this->click("link=Delete gene entry");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/genes\/GJB1[\s\S]delete$/',$this->getLocation()));
    $this->type("name=password", "test1234");
    $this->click("css=input[type=\"submit\"]");
    $this->waitForPageToLoad("30000");
    $this->assertEquals("Successfully deleted the gene information entry!", $this->getText("css=table[class=info]"));
    $this->waitForPageToLoad("4000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/genes$/',$this->getLocation()));
  }
}
?>