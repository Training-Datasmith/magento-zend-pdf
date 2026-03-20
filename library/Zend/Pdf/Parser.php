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
#require_once 'Zend/Pdf/Element.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_StringParser */
#require_once 'Zend/Pdf/StringParser.php';
/**
 * PDF file parser
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
#[\Allow_Dynamic_Properties]
class Zend_Pdf_Parser
{
    /**
     * String parser
     *
     * @var Zend_Pdf_StringParser
     */
    private $_string_parser;
    /**
     * Last PDF file trailer
     *
     * @var Zend_Pdf_Trailer_Keeper
     */
    private $_trailer;
    /**
     * PDF version specified in the file header
     *
     * @var string
     */
    private $_pdf_version;
    /**
     * Get length of source PDF
     */
    public function get_pdf_length(): int
    {
        return strlen($this->_string_parser->data);
    }
    /**
     * Get PDF String
     *
     * @return string
     */
    public function get_pdf_string()
    {
        return $this->_string_parser->data;
    }
    /**
     * PDF version specified in the file header
     *
     * @return string
     */
    public function get_pdf_version()
    {
        return $this->_pdf_version;
    }
    /**
     * Load XReference table and referenced objects
     *
     * @param integer $offset
     * @throws Zend_Pdf_Exception
     */
    private function _load_x_ref_table($offset): \Zend_Pdf_Trailer_Keeper
    {
        $this->_string_parser->offset = $offset;
        #require_once 'Zend/Pdf/Element/Reference/Table.php';
        $ref_table = new Zend_Pdf_Element_Reference_Table();
        #require_once 'Zend/Pdf/Element/Reference/Context.php';
        $context = new Zend_Pdf_Element_Reference_Context($this->_string_parser, $ref_table);
        $this->_string_parser->set_context($context);
        $next_lexeme = $this->_string_parser->read_lexeme();
        if ($next_lexeme == 'xref') {
            /**
             * Common cross-reference table
             */
            $this->_string_parser->skip_white_space();
            while (($next_lexeme = $this->_string_parser->read_lexeme()) != 'trailer') {
                if (!ctype_digit($next_lexeme)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Cross-reference table subheader values must contain only digits.', $this->_string_parser->offset - strlen($next_lexeme)));
                }
                $obj_num = (int) $next_lexeme;
                $ref_count = $this->_string_parser->read_lexeme();
                if (!ctype_digit($ref_count)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Cross-reference table subheader values must contain only digits.', $this->_string_parser->offset - strlen($ref_count)));
                }
                $this->_string_parser->skip_white_space();
                while ($ref_count > 0) {
                    $object_offset = substr($this->_string_parser->data, $this->_string_parser->offset, 10);
                    if (!ctype_digit($object_offset)) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Offset must contain only digits.', $this->_string_parser->offset));
                    }
                    // Force $objectOffset to be treated as decimal instead of octal number
                    for ($num_start = 0; $num_start < strlen($object_offset) - 1; $num_start++) {
                        if ($object_offset[$num_start] != '0') {
                            break;
                        }
                    }
                    $object_offset = substr($object_offset, $num_start);
                    $this->_string_parser->offset += 10;
                    if (strpos("\x00\t\n\f\r ", $this->_string_parser->data[$this->_string_parser->offset]) === false) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_string_parser->offset));
                    }
                    $this->_string_parser->offset++;
                    $gen_number = substr($this->_string_parser->data, $this->_string_parser->offset, 5);
                    if (!ctype_digit($object_offset)) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Offset must contain only digits.', $this->_string_parser->offset));
                    }
                    // Force $objectOffset to be treated as decimal instead of octal number
                    for ($num_start = 0; $num_start < strlen($gen_number) - 1; $num_start++) {
                        if ($gen_number[$num_start] != '0') {
                            break;
                        }
                    }
                    $gen_number = substr($gen_number, $num_start);
                    $this->_string_parser->offset += 5;
                    if (strpos("\x00\t\n\f\r ", $this->_string_parser->data[$this->_string_parser->offset]) === false) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_string_parser->offset));
                    }
                    $this->_string_parser->offset++;
                    $in_use_key = $this->_string_parser->data[$this->_string_parser->offset];
                    $this->_string_parser->offset++;
                    switch ($in_use_key) {
                        case 'f':
                            // free entry
                            unset($this->_ref_table[$obj_num . ' ' . $gen_number . ' R']);
                            $ref_table->add_reference($obj_num . ' ' . $gen_number . ' R', $object_offset, false);
                            break;
                        case 'n':
                            // in-use entry
                            $ref_table->add_reference($obj_num . ' ' . $gen_number . ' R', $object_offset, true);
                    }
                    if (!Zend_pdf_string_Parser::is_white_space(ord($this->_string_parser->data[$this->_string_parser->offset]))) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_string_parser->offset));
                    }
                    $this->_string_parser->offset++;
                    if (!Zend_pdf_string_Parser::is_white_space(ord($this->_string_parser->data[$this->_string_parser->offset]))) {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception(sprintf('PDF file cross-reference table syntax error. Offset - 0x%X. Value separator must be white space.', $this->_string_parser->offset));
                    }
                    $this->_string_parser->offset++;
                    $ref_count--;
                    $obj_num++;
                }
            }
            $trailer_dict_offset = $this->_string_parser->offset;
            $trailer_dict = $this->_string_parser->read_element();
            if (!$trailer_dict instanceof Zend_Pdf_Element_Dictionary) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X.  Dictionary expected after \'trailer\' keyword.', $trailer_dict_offset));
            }
        } else {
            $xref_stream = $this->_string_parser->get_object($offset, $context);
            if (!$xref_stream instanceof Zend_Pdf_Element_Object_Stream) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X.  Cross-reference stream expected.', $offset));
            }
            $trailer_dict = $xref_stream->dictionary;
            if ($trailer_dict->Type->value != 'XRef') {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X.  Cross-reference stream object must have /Type property assigned to /XRef.', $offset));
            }
            if ($trailer_dict->W === null || $trailer_dict->W->get_type() != Zend_Pdf_Element::TYPE_ARRAY) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Cross reference stream dictionary doesn\'t have W entry or it\'s not an array.', $offset));
            }
            $entry_field1size = $trailer_dict->W->items[0]->value;
            $entry_field2size = $trailer_dict->W->items[1]->value;
            $entry_field3size = $trailer_dict->W->items[2]->value;
            if ($entry_field2size == 0 || $entry_field3size == 0) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Wrong W dictionary entry. Only type field of stream entries has default value and could be zero length.', $offset));
            }
            $xref_stream_data = $xref_stream->value;
            if ($trailer_dict->Index !== null) {
                if ($trailer_dict->Index->get_type() != Zend_Pdf_Element::TYPE_ARRAY) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception(sprintf('PDF file syntax error. Offset - 0x%X. Cross reference stream dictionary Index entry must be an array.', $offset));
                }
                $sections = count($trailer_dict->Index->items) / 2;
            } else {
                $sections = 1;
            }
            $stream_offset = 0;
            $size = $entry_field1size + $entry_field2size + $entry_field3size;
            $entries = strlen($xref_stream_data) / $size;
            for ($count = 0; $count < $sections; $count++) {
                if ($trailer_dict->Index !== null) {
                    $obj_num = $trailer_dict->Index->items[$count * 2]->value;
                    $entries = $trailer_dict->Index->items[$count * 2 + 1]->value;
                } else {
                    $obj_num = 0;
                    $entries = $trailer_dict->Size->value;
                }
                for ($count2 = 0; $count2 < $entries; $count2++) {
                    if ($entry_field1size == 0) {
                        $type = 1;
                    } elseif ($entry_field1size == 1) {
                        // Optimyze one-byte field case
                        $type = ord($xref_stream_data[$stream_offset++]);
                    } else {
                        $type = Zend_pdf_string_Parser::parse_int_from_stream($xref_stream_data, $stream_offset, $entry_field1size);
                        $stream_offset += $entry_field1size;
                    }
                    if ($entry_field2size == 1) {
                        // Optimyze one-byte field case
                        $field2 = ord($xref_stream_data[$stream_offset++]);
                    } else {
                        $field2 = Zend_pdf_string_Parser::parse_int_from_stream($xref_stream_data, $stream_offset, $entry_field2size);
                        $stream_offset += $entry_field2size;
                    }
                    if ($entry_field3size == 1) {
                        // Optimyze one-byte field case
                        $field3 = ord($xref_stream_data[$stream_offset++]);
                    } else {
                        $field3 = Zend_pdf_string_Parser::parse_int_from_stream($xref_stream_data, $stream_offset, $entry_field3size);
                        $stream_offset += $entry_field3size;
                    }
                    switch ($type) {
                        case 0:
                            // Free object
                            $ref_table->add_reference($obj_num . ' ' . $field3 . ' R', $field2, false);
                            // Debug output:
                            // echo "Free object - $objNum $field3 R, next free - $field2\n";
                            break;
                        case 1:
                            // In use object
                            $ref_table->add_reference($obj_num . ' ' . $field3 . ' R', $field2, true);
                            // Debug output:
                            // echo "In-use object - $objNum $field3 R, offset - $field2\n";
                            break;
                        case 2:
                            // Object in an object stream
                            // Debug output:
                            // echo "Compressed object - $objNum 0 R, object stream - $field2 0 R, offset - $field3\n";
                            break;
                    }
                    $obj_num++;
                }
            }
            // $streamOffset . ' ' . strlen($xrefStreamData) . "\n";
            // "$entries\n";
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Cross-reference streams are not supported yet.');
        }
        #require_once 'Zend/Pdf/Trailer/Keeper.php';
        $trailer_obj = new Zend_Pdf_Trailer_Keeper($trailer_dict, $context);
        if ($trailer_dict->Prev instanceof Zend_Pdf_Element_Numeric || $trailer_dict->Prev instanceof Zend_Pdf_Element_Reference) {
            $trailer_obj->set_prev($this->_load_x_ref_table($trailer_dict->Prev->value));
            $context->get_ref_table()->set_parent($trailer_obj->get_prev()->get_ref_table());
        }
        /**
         * We set '/Prev' dictionary property to the current cross-reference section offset.
         * It doesn't correspond to the actual data, but is true when trailer will be used
         * as a trailer for next generated PDF section.
         */
        $trailer_obj->Prev = new Zend_Pdf_Element_Numeric($offset);
        return $trailer_obj;
    }
    /**
     * Get Trailer object
     *
     * @return Zend_Pdf_Trailer_Keeper
     */
    public function get_trailer()
    {
        return $this->_trailer;
    }
    /**
     * Object constructor
     *
     * Note: PHP duplicates string, which is sent by value, only of it's updated.
     * Thus we don't need to care about overhead
     *
     * @param mixed $source
     * @param boolean $load
     * @throws Zend_Exception
     */
    public function __construct($source, Zend_pdf_element_Factory_interface $factory, $load)
    {
        if ($load) {
            if (($pdf_file = @fopen($source, 'rb')) === false) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception("Can not open '{$source}' file for reading.");
            }
            $data = '';
            $byte_count = filesize($source);
            while ($byte_count > 0 && !feof($pdf_file)) {
                $next_block = fread($pdf_file, $byte_count);
                if ($next_block === false) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception("Error occured while '{$source}' file reading.");
                }
                $data .= $next_block;
                $byte_count -= strlen($next_block);
            }
            if ($byte_count != 0) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception("Error occured while '{$source}' file reading.");
            }
            fclose($pdf_file);
            $this->_string_parser = new Zend_pdf_string_Parser($data, $factory);
        } else {
            $this->_string_parser = new Zend_pdf_string_Parser($source, $factory);
        }
        $pdf_version_comment = $this->_string_parser->read_comment();
        if (substr($pdf_version_comment, 0, 5) != '%PDF-') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('File is not a PDF.');
        }
        $pdf_version = substr($pdf_version_comment, 5);
        if (version_compare($pdf_version, '0.9', '<') || version_compare($pdf_version, '1.61', '>=')) {
            /**
             * @todo
             * To support PDF versions 1.5 (Acrobat 6) and PDF version 1.7 (Acrobat 7)
             * Stream compression filter must be implemented (for compressed object streams).
             * Cross reference streams must be implemented
             */
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('Unsupported PDF version. Zend_Pdf supports PDF 1.0-1.4. Current version - \'%f\'', $pdf_version));
        }
        $this->_pdf_version = $pdf_version;
        $this->_string_parser->offset = strrpos($this->_string_parser->data, '%%EOF');
        if ($this->_string_parser->offset === false || strlen($this->_string_parser->data) - $this->_string_parser->offset > 7) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Pdf file syntax error. End-of-fle marker expected at the end of file.');
        }
        $this->_string_parser->offset--;
        /**
         * Go to end of cross-reference table offset
         */
        while (Zend_pdf_string_Parser::is_white_space(ord($this->_string_parser->data[$this->_string_parser->offset])) && $this->_string_parser->offset > 0) {
            $this->_string_parser->offset--;
        }
        /**
         * Go to the start of cross-reference table offset
         */
        while (!Zend_pdf_string_Parser::is_white_space(ord($this->_string_parser->data[$this->_string_parser->offset])) && $this->_string_parser->offset > 0) {
            $this->_string_parser->offset--;
        }
        /**
         * Go to the end of 'startxref' keyword
         */
        while (Zend_pdf_string_Parser::is_white_space(ord($this->_string_parser->data[$this->_string_parser->offset])) && $this->_string_parser->offset > 0) {
            $this->_string_parser->offset--;
        }
        /**
         * Go to the white space (eol marker) before 'startxref' keyword
         */
        $this->_string_parser->offset -= 9;
        $next_lexeme = $this->_string_parser->read_lexeme();
        if ($next_lexeme != 'startxref') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('Pdf file syntax error. \'startxref\' keyword expected. Offset - 0x%X.', $this->_string_parser->offset - strlen($next_lexeme)));
        }
        $start_xref = $this->_string_parser->read_lexeme();
        if (!ctype_digit($start_xref)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception(sprintf('Pdf file syntax error. Cross-reference table offset must contain only digits. Offset - 0x%X.', $this->_string_parser->offset - strlen($next_lexeme)));
        }
        $this->_trailer = $this->_load_x_ref_table($start_xref);
        $factory->set_object_count($this->_trailer->Size->value);
    }
    /**
     * Object destructor
     */
    public function __destruct()
    {
        $this->_string_parser->clean_up();
    }
}