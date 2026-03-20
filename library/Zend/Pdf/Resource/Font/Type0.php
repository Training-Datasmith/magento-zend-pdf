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
/** Internally used classes */
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Name.php';
/** Zend_Pdf_Resource_Font */
#require_once 'Zend/Pdf/Resource/Font.php';
/**
 * Adobe PDF composite fonts implementation
 *
 * A composite font is one whose glyphs are obtained from other fonts or from fontlike
 * objects called CIDFonts ({@link Zend_Pdf_Resource_Font_CidFont}), organized hierarchically.
 * In PDF, a composite font is represented by a font dictionary whose Subtype value is Type0;
 * this is also called a Type 0 font (the Type 0 font at the top level of the hierarchy is the
 * root font).
 *
 * In PDF, a Type 0 font is a CID-keyed font.
 *
 * CID-keyed fonts provide effective method to operate with multi-byte character encodings.
 *
 * The CID-keyed font architecture specifies the external representation of certain font programs,
 * called CMap and CIDFont files, along with some conventions for combining and using those files.
 *
 * A CID-keyed font is the combination of a CMap with one or more CIDFonts, simple fonts,
 * or composite fonts containing glyph descriptions.
 *
 * The term 'CID-keyed font' reflects the fact that CID (character identifier) numbers
 * are used to index and access the glyph descriptions in the font.
 *
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Font_Type0 extends Zend_Pdf_Resource_Font
{
    /**
     * Descendant CIDFont
     *
     * @var Zend_Pdf_Resource_Font_CidFont
     */
    private $_descendant_font;
    /**
     * Generate ToUnicode character map data
     */
    private static function get_to_unicode_c_map_data(): string
    {
        return '/CIDInit /ProcSet findresource begin ' . "\n" . '12 dict begin ' . "\n" . 'begincmap ' . "\n" . '/CIDSystemInfo ' . "\n" . '<</Registry (Adobe) ' . "\n" . '/Ordering (UCS) ' . "\n" . '/Supplement 0' . "\n" . '>> def' . "\n" . '/CMapName /Adobe-Identity-UCS def ' . "\n" . '/CMapType 2 def ' . "\n" . '1 begincodespacerange' . "\n" . '<0000> <FFFF> ' . "\n" . 'endcodespacerange ' . "\n" . '1 beginbfrange ' . "\n" . '<0000> <FFFF> <0000> ' . "\n" . 'endbfrange ' . "\n" . 'endcmap ' . "\n" . 'CMapName currentdict /CMap defineresource pop ' . "\n" . 'end ' . 'end ';
    }
    /**
     * Object constructor
     *
     */
    public function __construct(Zend_pdf_resource_font_cid_Font $descendant_font)
    {
        parent::__construct();
        $this->_object_factory->attach($descendant_font->get_factory());
        $this->_font_type = Zend_Pdf_Font::TYPE_TYPE_0;
        $this->_descendant_font = $descendant_font;
        $this->_font_names = $descendant_font->get_font_names();
        $this->_is_bold = $descendant_font->is_bold();
        $this->_is_italic = $descendant_font->is_italic();
        $this->_is_monospace = $descendant_font->is_monospace();
        $this->_underline_position = $descendant_font->get_underline_position();
        $this->_underline_thickness = $descendant_font->get_underline_thickness();
        $this->_strike_position = $descendant_font->get_strike_position();
        $this->_strike_thickness = $descendant_font->get_strike_thickness();
        $this->_units_per_em = $descendant_font->get_units_per_em();
        $this->_ascent = $descendant_font->get_ascent();
        $this->_descent = $descendant_font->get_descent();
        $this->_line_gap = $descendant_font->get_line_gap();
        $this->_resource->Subtype = new Zend_Pdf_Element_Name('Type0');
        $this->_resource->base_font = new Zend_Pdf_Element_Name($descendant_font->get_resource()->base_font->value);
        $this->_resource->descendant_fonts = new Zend_Pdf_Element_Array([$descendant_font->get_resource()]);
        $this->_resource->Encoding = new Zend_Pdf_Element_Name('Identity-H');
        $to_unicode = $this->_object_factory->new_stream_object(self::get_to_unicode_c_map_data());
        $this->_resource->to_unicode = $to_unicode;
    }
    /**
     * Returns an array of glyph numbers corresponding to the Unicode characters.
     *
     * Zend_Pdf uses 'Identity-H' encoding for Type 0 fonts.
     * So we don't need to perform any conversion
     *
     * See also {@link glyphNumberForCharacter()}.
     *
     * @param array $characterCodes Array of Unicode character codes (code points).
     * @return array Array of glyph numbers.
     */
    public function glyph_numbers_for_characters($character_codes)
    {
        return $character_codes;
    }
    /**
     * Returns the glyph number corresponding to the Unicode character.
     *
     * Zend_Pdf uses 'Identity-H' encoding for Type 0 fonts.
     * So we don't need to perform any conversion
     *
     * @param integer $characterCode Unicode character code (code point).
     * @return integer Glyph number.
     */
    public function glyph_number_for_character($character_code)
    {
        return $character_code;
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
        return $this->_descendant_font->get_covered_percentage($string, $char_encoding);
    }
    /**
     * Returns the widths of the glyphs.
     *
     * The widths are expressed in the font's glyph space. You are responsible
     * for converting to user space as necessary. See {@link unitsPerEm()}.
     *
     * Throws an exception if the glyph number is out of range.
     *
     * See also {@link widthForGlyph()}.
     *
     * @param array &$glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     * @throws Zend_Pdf_Exception
     */
    public function widths_for_glyphs($glyph_numbers)
    {
        return $this->_descendant_font->widths_for_chars($glyph_numbers);
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
        return $this->_descendant_font->width_for_char($glyph_number);
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
        return iconv($char_encoding, 'UTF-16BE', $string);
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
        return iconv('UTF-16BE', $char_encoding, $string);
    }
}