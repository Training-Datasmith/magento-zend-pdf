<?php

class Zend_Pdf_Element_NameTest extends Zend_Pdf_TestCase
{
    public function testSimpleName()
    {
        $el = new Zend_Pdf_Element_Name('Hello');
        $this->assertSame('/Hello', $el->toString());
    }

    public function testEscapeSpecials()
    {
        $this->assertSame('#20', Zend_Pdf_Element_Name::escape(' '));
        $this->assertSame(' ', Zend_Pdf_Element_Name::unescape('#20'));
        $this->assertSame('#23', Zend_Pdf_Element_Name::escape('#'));
        $this->assertSame('#', Zend_Pdf_Element_Name::unescape('#23'));
    }

    public function testNullByteRejected()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_Element_Name("A\x00B");
    }

    public function testVisibleAsciiRoundTrip()
    {
        $name = 'Type1';
        $this->assertSame($name, Zend_Pdf_Element_Name::unescape(Zend_Pdf_Element_Name::escape($name)));
    }
}
