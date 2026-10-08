<?php

class Zend_Pdf_StyleTest extends Zend_Pdf_TestCase
{
    public function testLineWidthInInstructions()
    {
        $style = new Zend_Pdf_Style();
        $style->setLineWidth(2);
        $this->assertSame(2, $style->getLineWidth());
        $this->assertContainsSubstring('2 w', $style->instructions());
    }

    public function testSolidDashPattern()
    {
        $style = new Zend_Pdf_Style();
        $style->setLineDashingPattern(Zend_Pdf_Page::LINE_DASHING_SOLID);
        $this->assertSame(array(), $style->getLineDashingPattern());
        $this->assertSame(0, $style->getLineDashingPhase());
        $this->assertContainsSubstring('[] 0 d', $style->instructions());
    }

    public function testCopyConstructorColors()
    {
        $a = new Zend_Pdf_Style();
        $a->setFillColor(new Zend_Pdf_Color_Rgb(1, 0, 0));
        $a->setLineColor(new Zend_Pdf_Color_Rgb(0, 0, 1));
        $b = new Zend_Pdf_Style($a);
        $instr = $b->instructions();
        $this->assertContainsSubstring('rg', $instr);
        $this->assertContainsSubstring('RG', $instr);
    }

    public function testDefaultInstructionsEmpty()
    {
        $style = new Zend_Pdf_Style();
        $this->assertSame('', $style->instructions());
    }
}
