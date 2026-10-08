<?php

class Zend_Pdf_ParserRoundTripTest extends Zend_Pdf_TestCase
{
    public function testMinimalDocumentLoadPreservesPageCount()
    {
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->properties['Creator'] = 'round-trip-test';

        $path = $this->registerTempFile(tempnam(sys_get_temp_dir(), 'zpdf'));
        file_put_contents($path, $pdf->render());

        $loaded = Zend_Pdf::load($path);
        $this->assertCount(2, $loaded->pages);
        $this->assertSame('round-trip-test', $loaded->properties['Creator']);
    }

    public function testReloadedDocumentRenders()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $page->setFont($font, 10);
        $page->drawText('persist', 72, 720);

        $path = $this->registerTempFile(tempnam(sys_get_temp_dir(), 'zpdf'));
        file_put_contents($path, $pdf->render());
        $loaded = Zend_Pdf::load($path);
        $out = $loaded->render();
        $this->assertContainsSubstring('%PDF-', $out);
        $this->assertContainsSubstring('(persist)', $out);
    }
}
