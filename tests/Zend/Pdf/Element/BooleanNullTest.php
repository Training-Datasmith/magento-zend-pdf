<?php

class Zend_Pdf_Element_BooleanNullTest extends Zend_Pdf_TestCase
{
    public function testBooleanLiterals()
    {
        $t = new Zend_Pdf_Element_Boolean(true);
        $this->assertSame('true', $t->toString());
        $this->assertSame(true, $t->toPhp());

        $f = new Zend_Pdf_Element_Boolean(false);
        $this->assertSame('false', $f->toString());
        $this->assertSame(false, $f->toPhp());
    }

    public function testNullElement()
    {
        $n = new Zend_Pdf_Element_Null();
        $this->assertSame('null', $n->toString());
        $this->assertNull($n->toPhp());
    }

    public function testBooleanRejectsInteger()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_Element_Boolean(1);
    }
}
