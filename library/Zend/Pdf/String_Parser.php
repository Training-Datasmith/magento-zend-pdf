<?php

declare (strict_types=1);
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Internally used classes */
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/String/Binary.php';
#require_once 'Zend/Pdf/Element/Boolean.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Null.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/Object.php';
#require_once 'Zend/Pdf/Element/Object/Stream.php';
#require_once 'Zend/Pdf/Element/Reference.php';
#require_once 'Zend/Pdf/Element/String.php';
/**
 * PDF string parser
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_string_Parser
{
    /**
     * Source PDF
     *
     * @var string
     */
    public $data = '';
    /**
     * Current position in a data
     *
     * @var integer
     */
    public $offset = 0;
    /**
     * Current reference context
     *
     * @var Zend_Pdf_Element_Reference_Context
     */
    private $_context;
    /**
     * Array of elements of the currently parsed object/trailer
     *
     * @var array
     */
    private $_elements = [];
    /**
     * PDF objects factory.
     *
     * @var Zend_Pdf_ElementFactory_Interface
     */
    private $_obj_factory;
    /**
     * Clean up resources.
     *
     * Clear current state to remove cyclic object references
     */
    public function clean_up()
    {
        $this->_context = null;
        $this->_elements = [];
        $this->_obj_factory = null;
    }
    /**
     * Character with code $chCode is white space
     *
     * @param integer $chCode
     */
    public static function is_white_space($ch_code): bool
    {
        if ($ch_code == 0x0 || $ch_code == 0x9 || $ch_code == 0xa || $ch_code == 0xc || $ch_code == 0xd || $ch_code == 0x20) {
            return true;
        }
        return false;
    }
    /**
     * Character with code $chCode is a delimiter character
     *
     * @param integer $chCode
     */
    public static function is_delimiter($ch_code): bool
    {
        if ($ch_code == 0x28 || $ch_code == 0x29 || $ch_code == 0x3c || $ch_code == 0x3e || $ch_code == 0x5b || $ch_code == 0x5d || $ch_code == 0x7b || $ch_code == 0x7d || $ch_code == 0x2f || $ch_code == 0x25) {
            return true;
        }
        return false;
    }
    /**
     * Skip white space
     *
     * @param boolean $skipComment
     */
    public function skip_white_space($skip_comment = true)
    {
        if ($skip_comment) {
            while (true) {
                $this->offset += strspn($this->data, "\x00\t\n\f\r ", $this->offset);
                if ($this->offset < strlen($this->data) && $this->data[$this->offset] == '%') {
                    // Skip comment
                    $this->offset += strcspn($this->data, "\r\n", $this->offset);
                } else {
                    // Non white space character not equal to '%' is found
                    return;
                }
            }
        } else {
            $this->offset += strspn($this->data, "\x00\t\n\f\r ", $this->offset);
        }
        //        /** Original (non-optimized) implementation. */
        //
        //        while ($this->offset < strlen($this->data)) {
        //            if (strpos("\x00\t\n\f\r ", $this->data[$this->offset]) !== false) {
        //                $this->offset++;
        //            } else if (ord($this->data[$this->offset]) == 0x25 && $skipComment) { // '%'
        //                $this->skipComment();
        //            } else {
        //                return;
        //            }
        //        }
    }
    /**
     * Skip comment
     */
    public function skip_comment()
    {
        while ($this->offset < strlen($this->data)) {
            if (ord($this->data[$this->offset]) != 0xa || ord($this->data[$this->offset]) != 0xd) {
                $this->offset++;
            } else {
                return;
            }
        }
    }
    /**
     * Read comment line
     */
    public function read_comment(): string
    {
        $this->skip_white_space(false);
        /** Check if it's a comment line */
        if ($this->data[$this->offset] != '%') {
            return '';
        }
        for ($start = $this->offset; $this->offset < strlen($this->data); $this->offset++) {
            if (ord($this->data[$this->offset]) == 0xa || ord($this->data[$this->offset]) == 0xd) {
                break;
            }
        }
        return substr($this->data, $start, $this->offset - $start);
    }
    /**
     * Returns next lexeme from a pdf stream
     *
     * @return string
     */
    public function read_lexeme()
    {
        // $this->skipWhiteSpace();
        while (true) {
            $this->offset += strspn($this->data, "\x00\t\n\f\r ", $this->offset);
            if ($this->offset < strlen($this->data) && $this->data[$this->offset] == '%') {
                $this->offset += strcspn($this->data, "\r\n", $this->offset);
            } else {
                break;
            }
        }
        if ($this->offset >= strlen($this->data)) {
            return '';
        }
        if (strpos('()<>[]{}/%', $this->data[$this->offset]) !== false) {
            switch (substr($this->data, $this->offset, 2)) {
                case '<<':
                    $this->offset += 2;
                    return '<<';
                case '>>':
                    $this->offset += 2;
                    return '>>';
                default:
                    return $this->data[$this->offset++];
            }
        } else {
            $start = $this->offset;
            $compare = '';
            if (version_compare(phpversion(), '5.2.5') >= 0) {
                $compare = "()<>[]{}/%\x00\t\n\f\r ";
            } else {
                $compare = "()<>[]{}/%\x00\t\n\r ";
            }
            $this->offset += strcspn($this->data, $compare, $this->offset);
            return substr($this->data, $start, $this->offset - $start);
        }
    }
    /**
     * Read elemental object from a PDF stream
     *
     * @return Zend_Pdf_Element
     * @throws Zend_Pdf_Exception
     */
    public function read_element($next_lexeme = null)
    {
        if ($next_lexeme === null) {
            $next_lexeme = $this->read_lexeme();
        }
        /**
         * Note: readElement() method is a public method and could be invoked from other classes.
         * If readElement() is used not by Zend_Pdf_StringParser::getObject() method, then we should not care
         * about _elements member management.
         */
        switch ($next_lexeme) {
            case '(':
                return $this->_elements[] = $this->_read_string();
            case '<':
                return $this->_elements[] = $this->_read_binary_string();
            case '/':
                return $this->_elements[] = new Zend_Pdf_Element_Name(Zend_Pdf_Element_Name::unescape($this->read_lexeme()));
            case '[':
                return $this->_elements[] = $this->_read_array();
            case '<<':
                return $this->_elements[] = $this->_read_dictionary();
            case ')':
            // fall through to next case
            case '>':
            // fall through to next case
            case ']':
            // fall through to next case
            case '>>':
            // fall through to next case
            case '{':
            // fall through to next case
            case '}':
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X.', $this->offset));
            default:
                if (strcasecmp($next_lexeme, 'true') == 0) {
                    return $this->_elements[] = new Zend_Pdf_Element_Boolean(true);
                }
                if (strcasecmp($next_lexeme, 'false') == 0) {
                    return $this->_elements[] = new Zend_Pdf_Element_Boolean(false);
                }
                if (strcasecmp($next_lexeme, 'null') == 0) {
                    return $this->_elements[] = new Zend_Pdf_Element_Null();
                }
                $ref = $this->_read_reference($next_lexeme);
                if ($ref !== null) {
                    return $this->_elements[] = $ref;
                }
                return $this->_elements[] = $this->_read_numeric($next_lexeme);
        }
    }
    /**
     * Read string PDF object
     * Also reads trailing ')' from a pdf stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _read_string(): \Zend_Pdf_Element_String
    {
        $start = $this->offset;
        $opened_brackets = 1;
        $this->offset += strcspn($this->data, '()\\', $this->offset);
        while ($this->offset < strlen($this->data)) {
            switch (ord($this->data[$this->offset])) {
                case 0x28:
                    // '(' - opened bracket in the string, needs balanced pair.
                    $this->offset++;
                    $opened_brackets++;
                    break;
                case 0x29:
                    // ')' - pair to the opened bracket
                    $this->offset++;
                    $opened_brackets--;
                    break;
                case 0x5c:
                    // '\\' - escape sequence, skip next char from a check
                    $this->offset += 2;
            }
            if ($opened_brackets == 0) {
                break;
                // end of string
            }
            $this->offset += strcspn($this->data, '()\\', $this->offset);
        }
        if ($opened_brackets != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Unexpected end of file while string reading. Offset - 0x%X. \')\' expected.', $start));
        }
        return new Zend_Pdf_Element_String(Zend_Pdf_Element_String::unescape(substr($this->data, $start, $this->offset - $start - 1)));
    }
    /**
     * Read binary string PDF object
     * Also reads trailing '>' from a pdf stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _read_binary_string(): \Zend_Pdf_Element_String_Binary
    {
        $start = $this->offset;
        $this->offset += strspn($this->data, "\x00\t\n\f\r 0123456789abcdefABCDEF", $this->offset);
        if ($this->offset >= strlen($this->data) - 1) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Unexpected end of file while reading binary string. Offset - 0x%X. \'>\' expected.', $start));
        }
        if ($this->data[$this->offset++] != '>') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Unexpected character while binary string reading. Offset - 0x%X.', $this->offset));
        }
        return new Zend_Pdf_Element_String_Binary(Zend_Pdf_Element_String_Binary::unescape(substr($this->data, $start, $this->offset - $start - 1)));
    }
    /**
     * Read array PDF object
     * Also reads trailing ']' from a pdf stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _read_array(): \Zend_Pdf_Element_Array
    {
        $elements = [];
        while (strlen($next_lexeme = $this->read_lexeme()) != 0) {
            if ($next_lexeme != ']') {
                $elements[] = $this->read_element($next_lexeme);
            } else {
                return new Zend_Pdf_Element_Array($elements);
            }
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Unexpected end of file while array reading. Offset - 0x%X. \']\' expected.', $this->offset));
    }
    /**
     * Read dictionary PDF object
     * Also reads trailing '>>' from a pdf stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _read_dictionary(): \Zend_Pdf_Element_Dictionary
    {
        $dictionary = new Zend_Pdf_Element_Dictionary();
        while (strlen($next_lexeme = $this->read_lexeme()) != 0) {
            if ($next_lexeme != '>>') {
                $name_start = $this->offset - strlen($next_lexeme);
                $name = $this->read_element($next_lexeme);
                $value = $this->read_element();
                if (!$name instanceof Zend_Pdf_Element_Name) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Name object expected while dictionary reading. Offset - 0x%X.', $name_start));
                }
                $dictionary->add($name, $value);
            } else {
                return $dictionary;
            }
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Unexpected end of file while dictionary reading. Offset - 0x%X. \'>>\' expected.', $this->offset));
    }
    /**
     * Read reference PDF object
     *
     * @param string $nextLexeme
     * @return Zend_Pdf_Element_Reference
     */
    private function _read_reference($next_lexeme = null)
    {
        $start = $this->offset;
        if ($next_lexeme === null) {
            $obj_num = $this->read_lexeme();
        } else {
            $obj_num = $next_lexeme;
        }
        if (!ctype_digit($obj_num)) {
            // it's not a reference
            $this->offset = $start;
            return null;
        }
        $gen_num = $this->read_lexeme();
        if (!ctype_digit($gen_num)) {
            // it's not a reference
            $this->offset = $start;
            return null;
        }
        $r_mark = $this->read_lexeme();
        if ($r_mark != 'R') {
            // it's not a reference
            $this->offset = $start;
            return null;
        }
        return new Zend_Pdf_Element_Reference((int) $obj_num, (int) $gen_num, $this->_context, $this->_obj_factory->resolve());
    }
    /**
     * Read numeric PDF object
     *
     * @param string $nextLexeme
     */
    private function _read_numeric($next_lexeme = null): \Zend_Pdf_Element_Numeric
    {
        if ($next_lexeme === null) {
            $next_lexeme = $this->read_lexeme();
        }
        return new Zend_Pdf_Element_Numeric($next_lexeme);
    }
    /**
     * Read inderect object from a PDF stream
     *
     * @param integer $offset
     * @return Zend_Pdf_Element_Object
     */
    public function get_object($offset, Zend_Pdf_Element_Reference_Context $context)
    {
        if ($offset === null) {
            return new Zend_Pdf_Element_Null();
        }
        // Save current offset to make getObject() reentrant
        $offset_save = $this->offset;
        $this->offset = $offset;
        $this->_context = $context;
        $this->_elements = [];
        $obj_num = $this->read_lexeme();
        if (!ctype_digit($obj_num)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Object number expected.', $this->offset - strlen($obj_num)));
        }
        $gen_num = $this->read_lexeme();
        if (!ctype_digit($gen_num)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Object generation number expected.', $this->offset - strlen($gen_num)));
        }
        $obj_keyword = $this->read_lexeme();
        if ($obj_keyword != 'obj') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. \'obj\' keyword expected.', $this->offset - strlen($obj_keyword)));
        }
        $obj_value = $this->read_element();
        $next_lexeme = $this->read_lexeme();
        if ($next_lexeme == 'endobj') {
            /**
             * Object is not generated by factory (thus it's not marked as modified object).
             * But factory is assigned to the obect.
             */
            $obj = new Zend_Pdf_Element_Object($obj_value, (int) $obj_num, (int) $gen_num, $this->_obj_factory->resolve());
            foreach ($this->_elements as $element) {
                $element->set_parent_object($obj);
            }
            // Restore offset value
            $this->offset = $offset_save;
            return $obj;
        }
        /**
         * It's a stream object
         */
        if ($next_lexeme != 'stream') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. \'endobj\' or \'stream\' keywords expected.', $this->offset - strlen($next_lexeme)));
        }
        if (!$obj_value instanceof Zend_Pdf_Element_Dictionary) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Stream extent must be preceded by stream dictionary.', $this->offset - strlen($next_lexeme)));
        }
        /**
         * References are automatically dereferenced at this moment.
         */
        $stream_length = $obj_value->Length->value;
        /**
         * 'stream' keyword must be followed by either cr-lf sequence or lf character only.
         * This restriction gives the possibility to recognize all cases exactly
         */
        if ($this->data[$this->offset] == "\r" && $this->data[$this->offset + 1] == "\n") {
            $this->offset += 2;
        } elseif ($this->data[$this->offset] == "\n") {
            $this->offset++;
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. \'stream\' must be followed by either cr-lf sequence or lf character only.', $this->offset - strlen($next_lexeme)));
        }
        $data_offset = $this->offset;
        $this->offset += $stream_length;
        $next_lexeme = $this->read_lexeme();
        if ($next_lexeme != 'endstream') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. \'endstream\' keyword expected.', $this->offset - strlen($next_lexeme)));
        }
        $next_lexeme = $this->read_lexeme();
        if ($next_lexeme != 'endobj') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. \'endobj\' keyword expected.', $this->offset - strlen($next_lexeme)));
        }
        $obj = new Zend_Pdf_Element_Object_Stream(substr($this->data, $data_offset, $stream_length), (int) $obj_num, (int) $gen_num, $this->_obj_factory->resolve(), $obj_value);
        foreach ($this->_elements as $element) {
            $element->set_parent_object($obj);
        }
        // Restore offset value
        $this->offset = $offset_save;
        return $obj;
    }
    /**
     * Get length of source string
     */
    public function get_length(): int
    {
        return strlen($this->data);
    }
    /**
     * Get source string
     *
     * @return string
     */
    public function get_string()
    {
        return $this->data;
    }
    /**
     * Parse integer value from a binary stream
     *
     * @param string $stream
     * @param integer $offset
     * @param integer $size
     */
    public static function parse_int_from_stream($stream, $offset, $size): int
    {
        $value = 0;
        for ($count = 0; $count < $size; $count++) {
            $value *= 256;
            $value += ord($stream[$offset + $count]);
        }
        return $value;
    }
    /**
     * Set current context
     */
    public function set_context(Zend_Pdf_Element_Reference_Context $context)
    {
        $this->_context = $context;
    }
    /**
     * Object constructor
     *
     * Note: PHP duplicates string, which is sent by value, only of it's updated.
     * Thus we don't need to care about overhead
     *
     * @param string $pdfString
     */
    public function __construct($source, Zend_pdf_element_Factory_interface $factory)
    {
        $this->data = $source;
        $this->_obj_factory = $factory;
    }
}