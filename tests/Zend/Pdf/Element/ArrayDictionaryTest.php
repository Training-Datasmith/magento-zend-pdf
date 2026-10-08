<?php

class Zend_Pdf_Element_ArrayDictionaryTest extends Zend_Pdf_TestCase
{
    public function testArrayToStringAndPhp()
    {
        $arr = new Zend_Pdf_Element_Array(array(
            new Zend_Pdf_Element_Numeric(1),
            new Zend_Pdf_Element_Numeric(2),
        ));
        $str = $arr->toString();
        $this->assertContainsSubstring('[', $str);
        $this->assertContainsSubstring(']', $str);
        $this->assertSame(array(1, 2), $arr->toPhp());
    }

    public function testArrayRejectsNonElement()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        new Zend_Pdf_Element_Array(array('bad'));
    }

    public function testDictionaryKeysAndUnset()
    {
        $dict = new Zend_Pdf_Element_Dictionary(array(
            'Type' => new Zend_Pdf_Element_Name('Page'),
        ));
        $this->assertSame(array('Type' => 'Page'), $dict->toPhp());
        $dict->Removed = null;
        $this->assertNotContains('Removed', $dict->getKeys());
    }

    public function testPhpToPdfTypes()
    {
        $dict = Zend_Pdf_Element::phpToPdf(array('A' => 1));
        $this->assertInstanceOf('Zend_Pdf_Element_Dictionary', $dict);
        $list = Zend_Pdf_Element::phpToPdf(array(1, 2));
        $this->assertInstanceOf('Zend_Pdf_Element_Array', $list);
        $bool = Zend_Pdf_Element::phpToPdf(true);
        $this->assertInstanceOf('Zend_Pdf_Element_Boolean', $bool);
    }
}
