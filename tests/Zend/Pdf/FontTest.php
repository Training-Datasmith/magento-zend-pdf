<?php

class Zend_Pdf_FontTest extends Zend_Pdf_TestCase
{
    public function testFontWithNameCachesInstance()
    {
        $a = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $b = Zend_Pdf_Font::fontWithName('Helvetica');
        $this->assertSame($a, $b);
    }

    public function testUnknownFontThrows()
    {
        try {
            Zend_Pdf_Font::fontWithName('NoSuchFont');
            $this->fail('Expected exception');
        } catch (Zend_Pdf_Exception $e) {
            $this->assertSame(Zend_Pdf_Exception::BAD_FONT_NAME, $e->getCode());
        }
    }

    public function testHelveticaMetrics()
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->assertSame('Helvetica', $font->getFontName(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8'));
        $this->assertFalse($font->isBold());
        $this->assertFalse($font->isItalic());
        $this->assertFalse($font->isMonospace());
        $this->assertSame(1000, $font->getUnitsPerEm());
        $this->assertSame(718, $font->getAscent());
        $this->assertSame(-207, $font->getDescent());
        $this->assertSame(275, $font->getLineGap());
        $this->assertSame(1200, $font->getLineHeight());
    }

    public function testHelveticaGlyphMapping()
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->assertSame(0x22, $font->glyphNumberForCharacter(0x0041));
        $this->assertSame(667, $font->widthForGlyph(0x22));
        $this->assertSame(0x01, $font->glyphNumberForCharacter(0x0020));
        $this->assertSame(278, $font->widthForGlyph(0x01));
    }

    public function testMissingGlyphFallback()
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->assertSame(0, $font->glyphNumberForCharacter(0x2603));
        $this->assertSame(0, $font->widthForGlyph(99999));
        $widths = $font->widthsForGlyphs(array(0x22));
        $this->assertArrayHasKey(0, $widths);
        $this->assertSame(667, $widths[0]);
    }

    public function testStandardFontStyleFlags()
    {
        $courier = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $this->assertTrue($courier->isMonospace());
        $this->assertSame(600, $courier->widthForGlyph($courier->glyphNumberForCharacter(0x0069)));
        $this->assertSame(600, $courier->widthForGlyph($courier->glyphNumberForCharacter(0x0057)));

        $timesItalic = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_TIMES_ITALIC);
        $this->assertTrue($timesItalic->isItalic());
        $this->assertFalse($timesItalic->isBold());

        $helveticaBold = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $this->assertTrue($helveticaBold->isBold());
    }

    public function testEncodeDecodeUtf8()
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $encoded = $font->encodeString('A', 'UTF-8');
        $this->assertSame('A', $encoded);
        $this->assertSame('A', $font->decodeString($encoded, 'UTF-8'));
    }

    public function testCoveredPercentage()
    {
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $this->assertSame(0, $font->getCoveredPercentage('', 'UTF-8'));
        $this->assertEquals(1.0, $font->getCoveredPercentage('A', 'UTF-8'));
        $this->assertEquals(0.5, $font->getCoveredPercentage("A\xE2\x98\x83", 'UTF-8'));
    }
}
