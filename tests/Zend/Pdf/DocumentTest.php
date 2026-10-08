<?php

class Zend_Pdf_DocumentTest extends Zend_Pdf_TestCase
{
    public function testNewDocumentRenderContainsPdfHeader()
    {
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $out = $pdf->render();
        $this->assertContainsSubstring('%PDF-', $out);
        $this->assertContainsSubstring('%%EOF', $out);
    }

    public function testTrappedTrueSerializesAsPdfNameNotCreationDate()
    {
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->properties['Trapped'] = true;
        $out = $pdf->render();
        $this->assertContainsSubstring('/Trapped /True', $out);
        $this->assertNotContainsSubstring('/CreationDate (1)', $out);
    }

    public function testTrappedFalseRendersAsPdfName()
    {
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->properties['Trapped'] = false;
        $out = $pdf->render();
        $this->assertContainsSubstring('/Trapped /False', $out);
    }

    public function testNamedDestinationRoundTrip()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $dest = Zend_Pdf_Destination_Fit::create($page);
        $pdf->setNamedDestination('intro', $dest);
        $this->assertSame($dest, $pdf->getNamedDestination('intro'));

        $path = $this->registerTempFile(tempnam(sys_get_temp_dir(), 'zpdf'));
        file_put_contents($path, $pdf->render());
        $loaded = Zend_Pdf::load($path);
        $this->assertNotNull($loaded->getNamedDestination('intro'));
    }

    public function testMemoryManagerRoundTrip()
    {
        $manager = new Zend_Memory_Manager();
        Zend_Pdf::setMemoryManager($manager);
        $this->assertSame($manager, Zend_Pdf::getMemoryManager());
    }

    public function testStringPropertyRoundTrip()
    {
        $pdf = new Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->properties['Title'] = 'Test Title';
        $out = $pdf->render();
        $this->assertContainsSubstring('/Title (Test Title)', $out);
    }
}
