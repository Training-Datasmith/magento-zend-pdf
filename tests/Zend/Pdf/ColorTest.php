<?php

class Zend_Pdf_ColorTest extends Zend_Pdf_TestCase
{
    public function testRgbComponentsAndOperators()
    {
        $rgb = new Zend_Pdf_Color_Rgb(1, 0, 0);
        $this->assertSame(array(1, 0, 0), $rgb->getComponents());
        $this->assertContainsSubstring('rg', $rgb->instructions(false));
        $this->assertContainsSubstring('RG', $rgb->instructions(true));
    }

    public function testRgbClamping()
    {
        $rgb = new Zend_Pdf_Color_Rgb(-1, 2, 0.5);
        $c = $rgb->getComponents();
        $this->assertSame(0, $c[0]);
        $this->assertSame(1, $c[1]);
    }

    public function testGrayAndCmykOperators()
    {
        $g = new Zend_Pdf_Color_GrayScale(0);
        $this->assertContainsSubstring(' g', $g->instructions(false));
        $this->assertContainsSubstring(' G', $g->instructions(true));
        $cmyk = new Zend_Pdf_Color_Cmyk(0.1, 0.2, 0.3, 0.4);
        $this->assertContainsSubstring('k', $cmyk->instructions(false));
        $this->assertContainsSubstring('K', $cmyk->instructions(true));
    }

    public function testHtmlHexRed()
    {
        $color = Zend_Pdf_Color_Html::color('#ff0000');
        $this->assertInstanceOf('Zend_Pdf_Color_Rgb', $color);
        $c = $color->getComponents();
        $this->assertSame(1.0, $c[0]);
        $this->assertSame(0.0, $c[1]);
        $this->assertSame(0.0, $c[2]);
    }

    public function testNamedColors()
    {
        $this->assertInstanceOf('Zend_Pdf_Color_GrayScale', Zend_Pdf_Color_Html::color('#000000'));
        $this->assertInstanceOf('Zend_Pdf_Color_GrayScale', Zend_Pdf_Color_Html::color('black'));
        $this->assertInstanceOf('Zend_Pdf_Color_GrayScale', Zend_Pdf_Color_Html::color('white'));
        $red = Zend_Pdf_Color_Html::color('red');
        $this->assertInstanceOf('Zend_Pdf_Color_Rgb', $red);
        $this->assertSame(1.0, $red->getComponents()[0]);
    }

    public function testInvalidHtmlColorThrows()
    {
        $this->expectException(Zend_Pdf_Exception::class);
        Zend_Pdf_Color_Html::color('not-a-color');
    }
}
