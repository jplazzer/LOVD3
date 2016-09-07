<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2016-09-07
 * Modified    : 2016-09-07
 * For LOVD    : 3.0-17
 *
 * Copyright   : 2016 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : M. Kroon <m.kroon@lumc.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/


require_once 'LOVDSeleniumBaseTestCase.php';

use \Facebook\WebDriver\WebDriverBy;
use \Facebook\WebDriver\WebDriverExpectedCondition;

class FindReplaceTest extends LOVDSeleniumWebdriverBaseTestCase
{

    public function testFindReplace()
    {
        // Upload variant data.
        $this->driver->get(ROOT_URL . '/src/variants/upload?create&type=VCF');
        $this->enterValue(WebDriverBy::name("variant_file"), ROOT_PATH .
                "../tests/test_data_files/ShortVCFfilev1.vcf");
        $uploadButton = $this->driver->findElement(WebDriverBy::xpath("//input[@value='Upload VCF file']"));
        $uploadButton->click();

        // Go to variant overview
        $this->driver->get(ROOT_URL . '/src/variants');

        // Open find and replace for Reference col.
        $this->openFRMenuForCol(6);

        // Click cancel button.
        $cancelButton = $this->driver->findElement(WebDriverBy::id('FRCancel_VOG'));
        $cancelButton->click();

        // Check if cancel button is hidden together with FR options menu by javascript.
        $this->waitUntil(WebDriverExpectedCondition::invisibilityOfElementLocated(
                WebDriverBy::id('FRCancel_VOG')));

        // Open find and replace for Reference col.
        $this->openFRMenuForCol(6);

        $columnReference = $this->driver->findElement(WebDriverBy::id('viewlistFRColDisplay_VOG'));
        $this->assertEquals($columnReference->getText(), 'Reference');

        $this->enterValue(WebDriverBy::name('FRReplace_VOG'), 'newvalue');

        $previewButton = $this->driver->findElement(WebDriverBy::id('FRPreview_VOG'));
        $previewButton->click();

        $previewColHeading = $this->driver->findElement(
                WebDriverBy::xpath('//th[@data-fieldname="VariantOnGenome/Reference_FR"]'));
        $this->assertContains($previewColHeading->getText(), 'Reference_(PREVIEW)');

        $aNewValueElements = $this->driver->findElements(WebDriverBy::xpath('//td[text()="newvalue"]'));
        $this->assertEquals(count($aNewValueElements), 25);

        $this->enterValue(WebDriverBy::xpath('//input[@type="password"]'), 'test1234');
        $submitButton = $this->driver->findElement(WebDriverBy::id('FRSubmit_VOG'));
        $submitButton->click();

        $this->chooseOkOnNextConfirmation();

        // Wait for refresh of viewlist with string "newvalue" in 6th column (Reference).
        $this->waitUntil(WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::xpath('//table[@id="viewlistTable_VOG"]/tbody/tr/td[position()=6 and text()="newvalue"]')));

        // Open find & replace menu for field "DNA change (genomic) (hg19)"
        $this->openFRMenuForCol(5);

        // Set search field to 'C' and preview.
        $this->enterValue(WebDriverBy::name('FRSearch_VOG'), 'C');
        $previewButton = $this->driver->findElement(WebDriverBy::id('FRPreview_VOG'));
        $previewButton->click();

        // Filter on 'Variant ID' > 10 during preview.
        $sSearchIDSelector = WebDriverBy::name('search_id_');
        $this->enterValue($sSearchIDSelector, '>10');
        $searchIDElement = $this->driver->findElement($sSearchIDSelector);
        // Use json_decode to send enter key to browser.
        $searchIDElement->sendKeys(json_decode('"\uE007"'));

        // Submit find & replace
        $this->enterValue(WebDriverBy::xpath('//input[@type="password"]'), 'test1234');
        $submitButton = $this->driver->findElement(WebDriverBy::id('FRSubmit_VOG'));
        $submitButton->click();

        // Check that filter has effect (otherwise 14 records are modified).
        $alertText = $this->driver->switchTo()->alert()->getText();
        $this->assertContains('You are about to modify 9 records', $alertText);

        $this->chooseOkOnNextConfirmation();
    }


    function openFRMenuForCol($nCol) {
        // Open the find & replace menu for the specified column index
        // (numbered 1..n from left to right)

        $gearOptionsLink = $this->driver->findElement(
                WebDriverBy::id('viewlistOptionsButton_VOG'));
        $gearOptionsLink->click();

        $FRMenuItem = $this->driver->findElement(
                WebDriverBy::partialLinkText('Find and replace text in column'));
        $FRMenuItem->click();

        $columnOverlay = $this->driver->findElement(
                WebDriverBy::xpath('//div[@class="vl_overlay"][' . $nCol . ']'));
        $columnOverlay->click();
    }
}
