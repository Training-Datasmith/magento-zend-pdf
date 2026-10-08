<?php

class Zend_Pdf_Element_StringTest extends Zend_Pdf_TestCase
{
    public function testEscapeUnescapeRoundTrip()
    {
        $raw = "()\n\\";
        $escaped = Zend_Pdf_Element_String::escape($raw);
        $this->assertSame($raw, Zend_Pdf_Element_String::unescape($escaped));
    }

    public function testToStringWrapsParentheses()
    {
        $el = new Zend_Pdf_Element_String('Hi');
        $this->assertSame('(Hi)', $el->toString());
    }

    public function testOctalEscape()
    {
        $this->assertSame('A', Zend_Pdf_Element_String::unescape('\\101'));
    }

    public function testTerminalOctalDigit()
    {
        $this->assertSame("A\x05", Zend_Pdf_Element_String::unescape('A\\5'));
    }

    public function testLineContinuationAfterBackslash()
    {
        $this->assertSame('AB', Zend_Pdf_Element_String::unescape("A\\\nB"));
    }

    public function testTrailingBackslashPreserved()
    {
        $this->assertSame('x\\', Zend_Pdf_Element_String::unescape('x\\'));
    }
}
