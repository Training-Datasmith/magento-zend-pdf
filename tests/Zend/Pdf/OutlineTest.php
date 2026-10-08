<?php

class Zend_Pdf_OutlineTest extends Zend_Pdf_TestCase
{
    public function testCreateWithStringTarget()
    {
        $outline = Zend_Pdf_Outline::create('Chapter', 'intro');
        $target = $outline->getTarget();
        $this->assertInstanceOf('Zend_Pdf_Destination_Named', $target);
        $this->assertSame('intro', $target->getName());
    }

    public function testOutlineDumpIncludesExplicitDest()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $pdf->outlines[] = Zend_Pdf_Outline::create(array(
            'title' => 'Chapter',
            'target' => Zend_Pdf_Destination_Fit::create($page),
        ));

        $out = $pdf->render();
        $this->assertContainsSubstring('/Outlines', $out);
        $this->assertContainsSubstring('/Title (Chapter)', $out);
        $this->assertContainsSubstring('/Dest [', $out);
        $this->assertContainsSubstring('/Fit', $out);
    }

    public function testSetTargetString()
    {
        $outline = Zend_Pdf_Outline::create('T', null);
        $outline->setTarget('named-dest');
        $this->assertSame('named-dest', $outline->getTarget()->getName());
    }
}
