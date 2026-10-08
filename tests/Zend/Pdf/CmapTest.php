<?php

class Zend_Pdf_CmapTest extends Zend_Pdf_TestCase
{
    private function byteEncodingTable($glyphAtFirstByte)
    {
        $data = pack('nnn', 0, 262, 0) . str_repeat("\0", 256);
        $data[6] = chr($glyphAtFirstByte);
        return $data;
    }

    public function testByteEncodingMapsFirstByte()
    {
        $cmap = new Zend_Pdf_Cmap_ByteEncoding($this->byteEncodingTable(42));
        $this->assertSame(42, $cmap->glyphNumberForCharacter(0x00));
        $this->assertSame(Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH, $cmap->glyphNumberForCharacter(0x9999));
    }

    public function testByteEncodingRejectsBadLength()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_Cmap_ByteEncoding(str_repeat('x', 100));
    }

    public function testStaticByteEncoding()
    {
        $cmap = Zend_Pdf_Cmap::cmapWithTypeData(
            Zend_Pdf_Cmap::TYPE_BYTE_ENCODING_STATIC,
            array(0x41 => 4)
        );
        $this->assertSame(4, $cmap->glyphNumberForCharacter(0x41));
        $this->assertSame(0, $cmap->glyphNumberForCharacter(0x42));
    }

    public function testStaticRequiresArray()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Cmap::cmapWithTypeData(Zend_Pdf_Cmap::TYPE_BYTE_ENCODING_STATIC, 'nope');
    }

    public function testTrimmedTableRange()
    {
        $data = pack('nnnnn', 6, 14, 0, 65, 2) . pack('nn', 10, 11);
        $cmap = new Zend_Pdf_Cmap_TrimmedTable($data);
        $this->assertSame(10, $cmap->glyphNumberForCharacter(65));
        $this->assertSame(11, $cmap->glyphNumberForCharacter(66));
        $this->assertSame(0, $cmap->glyphNumberForCharacter(64));
    }

    public function testUnsupportedCmapTypesThrow()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Cmap::cmapWithTypeData(Zend_Pdf_Cmap::TYPE_SEGMENTED_COVERAGE, '');
    }

    public function testUnknownCmapTypeThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Cmap::cmapWithTypeData(255, '');
    }
}
