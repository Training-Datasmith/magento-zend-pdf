<?php

class Zend_Pdf_PageDrawingTest extends Zend_Pdf_TestCase
{
    public function testDrawLineAndTextEmitOperators()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;

        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $page->setFont($font, 12);
        $page->drawLine(10, 10, 100, 100);
        $page->drawText('Hello', 50, 700);

        $contents = $this->getPageContents($page);
        $this->assertContainsSubstring('100 100 l', $contents);
        $this->assertContainsSubstring(' Tj', $contents);
        $this->assertContainsSubstring('(Hello)', $contents);
    }

    public function testDrawRectangleEmitsReOperator()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $page->drawRectangle(10, 10, 50, 50, Zend_Pdf_Page::SHAPE_DRAW_STROKE);
        $contents = $this->getPageContents($page);
        $this->assertContainsSubstring(' re', $contents);
        $this->assertContainsSubstring(' S', $contents);
    }

    public function testSaveRestoreOperators()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $page->saveGS();
        $page->restoreGS();
        $contents = $this->getPageContents($page);
        $this->assertContainsSubstring(' q', $contents);
        $this->assertContainsSubstring(' Q', $contents);
    }
}
