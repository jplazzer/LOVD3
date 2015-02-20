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
    $this->open("/svn/LOVD3/trunk/src/variants/upload?create&type=VCF");
    $this->assertEquals("To access this area, you need at least Submitter (data owner) clearance.", $this->getText("css=table[class=info]"));
  }
}
?>