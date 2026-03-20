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
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** @see Zend_Pdf_Resource_Font */
#require_once 'Zend/Pdf/Resource/Font.php';
/**
 * Extracted fonts implementation
 *
 * Thes class allows to extract fonts already mentioned within PDF document and use them
 * for text drawing.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Font_Extracted extends Zend_Pdf_Resource_Font
{
    /**
     * Messages
     */
    public const TYPE_NOT_SUPPORTED = 'Unsupported font type.';
    public const ENCODING_NOT_SUPPORTED = 'Font encoding is not supported';
    public const OPERATION_NOT_SUPPORTED = 'Operation is not supported for extracted fonts';
    /**
     * Extracted font encoding
     *
     * Only 'Identity-H' and 'WinAnsiEncoding' encodings are supported now
     *
     * @var string
     */
    protected $_encoding;
    /**
     * Object constructor
     *
     * $fontDictionary is a Zend_Pdf_Element_Reference or Zend_Pdf_Element_Object object
     *
     * @param mixed $fontDictionary
     * @throws Zend_Pdf_Exception
     */
    public function __construct($font_dictionary)
    {
        // Extract object factory and resource object from font dirctionary object
        $this->_object_factory = $font_dictionary->get_factory();
        $this->_resource = $font_dictionary;
        if ($font_dictionary->Encoding !== null) {
            $this->_encoding = $font_dictionary->Encoding->value;
        }
        switch ($font_dictionary->Subtype->value) {
            case 'Type0':
                // Composite type 0 font
                if (count($font_dictionary->descendant_fonts->items) != 1) {
                    // Multiple descendant fonts are not supported
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception(self::TYPE_NOT_SUPPORTED);
                }
                $font_dictionary_iterator = $font_dictionary->descendant_fonts->items->getIterator();
                $font_dictionary_iterator->rewind();
                $descendant_font = $font_dictionary_iterator->current();
                $font_descriptor = $descendant_font->font_descriptor;
                break;
            case 'Type1':
                if ($font_dictionary->font_descriptor === null) {
                    // That's one of the standard fonts
                    $standard_font = Zend_Pdf_Font::font_with_name($font_dictionary->base_font->value);
                    $this->_font_names = $standard_font->get_font_names();
                    $this->_is_bold = $standard_font->is_bold();
                    $this->_is_italic = $standard_font->is_italic();
                    $this->_is_monospace = $standard_font->is_monospace();
                    $this->_underline_position = $standard_font->get_underline_position();
                    $this->_underline_thickness = $standard_font->get_underline_thickness();
                    $this->_strike_position = $standard_font->get_strike_position();
                    $this->_strike_thickness = $standard_font->get_strike_thickness();
                    $this->_units_per_em = $standard_font->get_units_per_em();
                    $this->_ascent = $standard_font->get_ascent();
                    $this->_descent = $standard_font->get_descent();
                    $this->_line_gap = $standard_font->get_line_gap();
                    return;
                }
                $font_descriptor = $font_dictionary->font_descriptor;
                break;
            case 'TrueType':
                $font_descriptor = $font_dictionary->font_descriptor;
                break;
            default:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception(self::TYPE_NOT_SUPPORTED);
        }
        $this->_font_names[Zend_Pdf_Font::NAME_POSTSCRIPT]['en'] = iconv('UTF-8', 'UTF-16BE', $font_dictionary->base_font->value);
        $this->_is_bold = false;
        // this property is actually not used anywhere
        $this->_is_italic = ($font_descriptor->Flags->value & 1 << 6) != 0;
        // Bit-7 is set
        $this->_is_monospace = ($font_descriptor->Flags->value & 1 << 0) != 0;
        // Bit-1 is set
        $this->_underline_position = null;
        // Can't be extracted
        $this->_underline_thickness = null;
        // Can't be extracted
        $this->_strike_position = null;
        // Can't be extracted
        $this->_strike_thickness = null;
        // Can't be extracted
        $this->_units_per_em = null;
        // Can't be extracted
        $this->_ascent = $font_descriptor->Ascent->value;
        $this->_descent = $font_descriptor->Descent->value;
        $this->_line_gap = null;
        // Can't be extracted
    }
    /**
     * Returns an array of glyph numbers corresponding to the Unicode characters.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumberForCharacter()}.
     *
     * @param array $characterCodes Array of Unicode character codes (code points).
     * @return array Array of glyph numbers.
     */
    public function glyph_numbers_for_characters($character_codes)
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::OPERATION_NOT_SUPPORTED);
    }
    /**
     * Returns the glyph number corresponding to the Unicode character.
     *
     * If a particular character doesn't exist in this font, the special 'missing
     * character glyph' will be substituted.
     *
     * See also {@link glyphNumbersForCharacters()} which is optimized for bulk
     * operations.
     *
     * @param integer $characterCode Unicode character code (code point).
     * @return integer Glyph number.
     */
    public function glyph_number_for_character($character_code)
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::OPERATION_NOT_SUPPORTED);
    }
    /**
     * Returns a number between 0 and 1 inclusive that indicates the percentage
     * of characters in the string which are covered by glyphs in this font.
     *
     * Since no one font will contain glyphs for the entire Unicode character
     * range, this method can be used to help locate a suitable font when the
     * actual contents of the string are not known.
     *
     * Note that some fonts lie about the characters they support. Additionally,
     * fonts don't usually contain glyphs for control characters such as tabs
     * and line breaks, so it is rare that you will get back a full 1.0 score.
     * The resulting value should be considered informational only.
     *
     * @param string $string
     * @param string $charEncoding (optional) Character encoding of source text.
     *   If omitted, uses 'current locale'.
     * @return float
     */
    public function get_covered_percentage($string, $char_encoding = '')
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::OPERATION_NOT_SUPPORTED);
    }
    /**
     * Returns the widths of the glyphs.
     *
     * The widths are expressed in the font's glyph space. You are responsible
     * for converting to user space as necessary. See {@link unitsPerEm()}.
     *
     * See also {@link widthForGlyph()}.
     *
     * @param array $glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     * @throws Zend_Pdf_Exception
     */
    public function widths_for_glyphs($glyph_numbers)
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::OPERATION_NOT_SUPPORTED);
    }
    /**
     * Returns the width of the glyph.
     *
     * Like {@link widthsForGlyphs()} but used for one glyph at a time.
     *
     * @param integer $glyphNumber
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function width_for_glyph($glyph_number)
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::OPERATION_NOT_SUPPORTED);
    }
    /**
     * Convert string to the font encoding.
     *
     * The method is used to prepare string for text drawing operators
     *
     * @param string $string
     * @param string $charEncoding Character encoding of source text.
     * @return string
     */
    public function encode_string($string, $char_encoding)
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv($char_encoding, 'UTF-16BE', $string);
        }
        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv($char_encoding, 'CP1252//IGNORE', $string);
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::ENCODING_NOT_SUPPORTED);
    }
    /**
     * Convert string from the font encoding.
     *
     * The method is used to convert strings retrieved from existing content streams
     *
     * @param string $string
     * @param string $charEncoding Character encoding of resulting text.
     * @return string
     */
    public function decode_string($string, $char_encoding)
    {
        if ($this->_encoding == 'Identity-H') {
            return iconv('UTF-16BE', $char_encoding, $string);
        }
        if ($this->_encoding == 'WinAnsiEncoding') {
            return iconv('CP1252', $char_encoding, $string);
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception(self::ENCODING_NOT_SUPPORTED);
    }
}