<?php

class Zend_Pdf_Element_NumericTest extends Zend_Pdf_TestCase
{
    public function testIntegerToString()
    {
        $el = new Zend_Pdf_Element_Numeric(10);
        $this->assertSame('10', $el->toString());
        $this->assertSame(10, $el->toPhp());
    }

    public function testFloatToStringNotExponential()
    {
        $el = new Zend_Pdf_Element_Numeric(1.5);
        $this->assertSame('1.5', $el->toString());
        $this->assertNotContainsSubstring('e', strtolower($el->toString()));
    }

    public function testFloatOneTenthFormatsWithDecimal()
    {
        $el = new Zend_Pdf_Element_Numeric(0.1);
        $str = $el->toString();
        $this->assertContainsSubstring('.', $str);
        $this->assertLessThan(1e-9, abs((float) $str - 0.1));
    }

    public function testNonNumericThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_Element_Numeric('nope');
    }
}
