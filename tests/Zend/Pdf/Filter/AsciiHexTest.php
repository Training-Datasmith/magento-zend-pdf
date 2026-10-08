<?php

class Zend_Pdf_Filter_AsciiHexTest extends Zend_Pdf_TestCase
{
    public function testEncodeAddsMarker()
    {
        $this->assertSame('41>', Zend_Pdf_Filter_AsciiHex::encode('A'));
    }

    public function testDecodeRoundTrip()
    {
        $this->assertSame('A', Zend_Pdf_Filter_AsciiHex::decode('41>'));
        $this->assertSame('A', Zend_Pdf_Filter_AsciiHex::decode('4 1>'));
        $this->assertSame('a', Zend_Pdf_Filter_AsciiHex::decode('61>'));
    }

    public function testOddDigitPadding()
    {
        $this->assertSame("\xA0", Zend_Pdf_Filter_AsciiHex::decode('A>'));
    }

    public function testCommentSkipped()
    {
        $this->assertSame('A', Zend_Pdf_Filter_AsciiHex::decode("41%comment\n>"));
    }

    public function testInvalidDigitThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Filter_AsciiHex::decode('4G>');
    }
}
