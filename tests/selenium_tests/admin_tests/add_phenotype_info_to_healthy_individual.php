<?php
require_once 'LOVDSeleniumBaseTestCase.php';

class AddPhenotypeInfoToHealthyIndividualTest extends LOVDSeleniumBaseTestCase
{
    public function testAddPhenotypeInfoToHealthyIndividual()
    {
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/submit\/individual\/00000001$/', $this->getLocation()));
        $this->click("//div/table/tbody/tr/td/table/tbody/tr/td[2]/b");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/phenotypes[\s\S]create&target=00000001$/', $this->getLocation()));
        $this->selectWindow("null");
        $this->type("name=Phenotype/Age", "35y");
        $this->select("name=owned_by", "label=LOVD3 Admin (#00001)");
        $this->select("name=statusid", "label=Public");
        $this->click("//input[@value='Create phenotype information entry']");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("Successfully created the phenotype entry!", $this->getText("css=table[class=info]"));
        $this->waitForPageToLoad("4000");
    }
}