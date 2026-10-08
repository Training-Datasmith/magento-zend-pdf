<?php

class Zend_Pdf_Filter_RunLengthTest extends Zend_Pdf_TestCase
{
    public function testRepeatedRunEncoding()
    {
        $encoded = Zend_Pdf_Filter_RunLength::encode('aaa');
        $this->assertSame(chr(254) . 'a', substr($encoded, 0, 2));
        $this->assertSame(chr(128), substr($encoded, -1));
    }

    public function testRoundTripMixed()
    {
        $original = "ABCD" . str_repeat('x', 5) . 'Z';
        $this->assertSame($original, Zend_Pdf_Filter_RunLength::decode(Zend_Pdf_Filter_RunLength::encode($original)));
    }

    public function testLiteralLength128Block()
    {
        $original = str_repeat('Q', 128);
        $this->assertSame($original, Zend_Pdf_Filter_RunLength::decode(Zend_Pdf_Filter_RunLength::encode($original)));
    }

    public function testSingleLiteralByte()
    {
        $this->assertSame('Z', Zend_Pdf_Filter_RunLength::decode(chr(0) . 'Z' . chr(128)));
    }
}
