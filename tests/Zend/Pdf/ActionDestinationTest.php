<?php

class Zend_Pdf_ActionDestinationTest extends Zend_Pdf_TestCase
{
    public function testFitDestinationResourceShape()
    {
        $pdf = new Zend_Pdf();
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        $dest = Zend_Pdf_Destination_Fit::create($page);
        $resource = $dest->getResource();
        $this->assertInstanceOf('Zend_Pdf_Element_Array', $resource);
        $this->assertCount(2, $resource->items);
        $this->assertSame('Fit', $resource->items[1]->value);
    }

    public function testGoToWithStringNamedDestination()
    {
        $action = Zend_Pdf_Action_GoTo::create('chapter-one');
        $dest = $action->getDestination();
        $this->assertInstanceOf('Zend_Pdf_Destination_Named', $dest);
        $this->assertSame('chapter-one', $dest->getName());
    }

    public function testNamedDestinationCreate()
    {
        $named = Zend_Pdf_Destination_Named::create('anchor');
        $this->assertSame('anchor', $named->getName());
        $this->assertInstanceOf('Zend_Pdf_Element_String', $named->getResource());
    }
}
