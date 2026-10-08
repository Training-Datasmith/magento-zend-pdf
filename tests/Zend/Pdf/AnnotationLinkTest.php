<?php

class Zend_Pdf_AnnotationLinkTest extends Zend_Pdf_TestCase
{
    public function testCreateWithStringNamedDestination()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;

        $link = Zend_Pdf_Annotation_Link::create(0, 0, 100, 100, 'MyDest');
        $page->attachAnnotation($link);

        $dest = $link->getDestination();
        $this->assertInstanceOf('Zend_Pdf_Destination_Named', $dest);
        $this->assertSame('MyDest', $dest->getName());
    }

    public function testSetDestinationStringUsesNamedCreate()
    {
        $link = Zend_Pdf_Annotation_Link::create(0, 0, 10, 10, Zend_Pdf_Destination_Named::create('first'));
        $link->setDestination('second');
        $dest = $link->getDestination();
        $this->assertInstanceOf('Zend_Pdf_Destination_Named', $dest);
        $this->assertSame('second', $dest->getName());
    }

    public function testSetDestinationExplicitPage()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $fit = Zend_Pdf_Destination_Fit::create($page);
        $link = Zend_Pdf_Annotation_Link::create(0, 0, 10, 10, $fit);
        $dest = $link->getDestination();
        $this->assertInstanceOf('Zend_Pdf_Destination_Fit', $dest);
    }
}
