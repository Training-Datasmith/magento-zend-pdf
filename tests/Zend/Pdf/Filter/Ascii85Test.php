<?php

class Zend_Pdf_Filter_Ascii85Test extends Zend_Pdf_TestCase
{
    public function testManSpaceSpecPrefix()
    {
        $encoded = Zend_Pdf_Filter_Ascii85::encode('Man ');
        $this->assertSame('9jqo^', substr($encoded, 0, 5));
        $this->assertEndsWith('~>', $encoded);
    }

    public function testZeroChunkUsesZ()
    {
        $encoded = Zend_Pdf_Filter_Ascii85::encode("\x00\x00\x00\x00");
        $this->assertContainsSubstring('z', $encoded);
    }

    public function testRoundTripSamples()
    {
        foreach (array('Man ', "\x00\x00\x00\x00", "\xff\xff\xff\xff", 'hello') as $bytes) {
            $this->assertSame($bytes, Zend_Pdf_Filter_Ascii85::decode(Zend_Pdf_Filter_Ascii85::encode($bytes)));
        }
    }

    public function testWhitespaceIgnoredOnDecode()
    {
        $inner = Zend_Pdf_Filter_Ascii85::encode('Man ');
        $spaced = substr($inner, 0, 2) . "\n " . substr($inner, 2);
        $this->assertSame('Man ', Zend_Pdf_Filter_Ascii85::decode($spaced));
    }

    public function testMissingEodThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Filter_Ascii85::decode('AAAAA');
    }
}
