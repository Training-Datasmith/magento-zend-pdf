<?php

class Zend_Pdf_StringParserTest extends Zend_Pdf_TestCase
{
    /** @var Zend_Pdf_StringParser */
    private $parser;

    protected function setUp()
    {
        parent::setUp();
        $factory = Zend_Pdf_ElementFactory::createFactory(1);
        $this->parser = new Zend_Pdf_StringParser('', $factory);
    }

    public function testWhitespaceAndDelimiterHelpers()
    {
        $this->assertTrue(Zend_Pdf_StringParser::isWhiteSpace(0x20));
        $this->assertFalse(Zend_Pdf_StringParser::isWhiteSpace(0x41));
        $this->assertTrue(Zend_Pdf_StringParser::isDelimiter(0x25));
        $this->assertFalse(Zend_Pdf_StringParser::isDelimiter(0x41));
    }

    public function testParseIntFromStream()
    {
        $this->assertSame(258, Zend_Pdf_StringParser::parseIntFromStream("\x01\x02", 0, 2));
    }

    public function testReadLexemeDictionaryMarkers()
    {
        $this->parser->data = '<< /Name (Hi) >>';
        $this->parser->offset = 0;
        $this->assertSame('<<', $this->parser->readLexeme());
        $this->assertSame('/', $this->parser->readLexeme());
        $this->assertSame('Name', $this->parser->readLexeme());
        $this->assertSame('(', $this->parser->readLexeme());
    }

    public function testReadElementVariants()
    {
        $this->parser->data = '(Hi) true null [1] << /A 1 >> /A#20B';
        $this->parser->offset = 0;
        $str = $this->parser->readElement();
        $this->assertInstanceOf('Zend_Pdf_Element_String', $str);
        $this->assertSame('Hi', $str->value);

        $this->parser->data = '<4869> ';
        $this->parser->offset = 0;
        $bin = $this->parser->readElement();
        $this->assertInstanceOf('Zend_Pdf_Element_String_Binary', $bin);
        $this->assertSame('Hi', $bin->value);

        $this->parser->data = '/A#20B ';
        $this->parser->offset = 0;
        $name = $this->parser->readElement();
        $this->assertInstanceOf('Zend_Pdf_Element_Name', $name);
        $this->assertSame('A B', $name->value);

        $this->parser->data = '[1 2] ';
        $this->parser->offset = 0;
        $arr = $this->parser->readElement();
        $this->assertInstanceOf('Zend_Pdf_Element_Array', $arr);
        $this->assertCount(2, $arr->items);

        $this->parser->data = '<< /A 1 >> ';
        $this->parser->offset = 0;
        $dict = $this->parser->readElement();
        $this->assertInstanceOf('Zend_Pdf_Element_Dictionary', $dict);
        $this->assertEquals(1, $dict->A->value);

        $this->parser->data = 'true';
        $this->parser->offset = 0;
        $this->assertTrue($this->parser->readElement()->value);

        $this->parser->data = 'null';
        $this->parser->offset = 0;
        $this->assertInstanceOf('Zend_Pdf_Element_Null', $this->parser->readElement());
    }

    public function testSkipWhitespaceSkipsComment()
    {
        $this->parser->data = "  % comment\n/Name";
        $this->parser->offset = 0;
        $this->parser->skipWhiteSpace(true);
        $this->assertSame('/', $this->parser->data[$this->parser->offset]);
    }

    public function testUnbalancedStringThrows()
    {
        $this->parser->data = '(Hi';
        $this->parser->offset = 0;
        $this->expectException(Zend_Pdf_Exception::class);
        $this->parser->readElement('(');
    }

    public function testStrayCloserThrows()
    {
        $this->parser->data = ']';
        $this->parser->offset = 0;
        $this->expectException(Zend_Pdf_Exception::class);
        $this->parser->readElement(']');
    }
}
