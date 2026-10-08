<?php

class Zend_Pdf_FileParserDataSourceTest extends Zend_Pdf_TestCase
{
    public function testEmptyStringRejected()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_FileParserDataSource_String('');
    }

    public function testReadBytesAdvancesOffset()
    {
        $src = new Zend_Pdf_FileParserDataSource_String('ABCD');
        $this->assertSame('AB', $src->readBytes(2));
        $this->assertSame('CD', $src->readBytes(2));
        $this->expectException(Zend_Pdf_Exception::class);
        $src->readBytes(1);
    }

    public function testToStringShowsSize()
    {
        $src = new Zend_Pdf_FileParserDataSource_String('ABCD');
        $this->assertContainsSubstring('4', (string) $src);
    }
}
