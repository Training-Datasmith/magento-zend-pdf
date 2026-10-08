<?php

class Zend_Pdf_DateTest extends Zend_Pdf_TestCase
{
    public function testPdfDateUtc()
    {
        date_default_timezone_set('UTC');
        $this->assertSame("D:19700101000000+00'00'", Zend_Pdf::pdfDate(0));
        $this->assertSame("D:20000101000000+00'00'", Zend_Pdf::pdfDate(946684800));
    }
}
