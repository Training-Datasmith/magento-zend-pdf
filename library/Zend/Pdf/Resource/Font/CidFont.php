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
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String.php';
/** Zend_Pdf_Resource_Font */
#require_once 'Zend/Pdf/Resource/Font.php';
/**
 * Adobe PDF CIDFont font object implementation
 *
 * A CIDFont program contains glyph descriptions that are accessed using a CID as
 * the character selector. There are two types of CIDFont. A Type 0 CIDFont contains
 * glyph descriptions based on Adobe’s Type 1 font format, whereas those in a
 * Type 2 CIDFont are based on the TrueType font format.
 *
 * A CIDFont dictionary is a PDF object that contains information about a CIDFont program.
 * Although its Type value is Font, a CIDFont is not actually a font. It does not have an Encoding
 * entry, it cannot be listed in the Font subdictionary of a resource dictionary, and it cannot be
 * used as the operand of the Tf operator. It is used only as a descendant of a Type 0 font.
 * The CMap in the Type 0 font is what defines the encoding that maps character codes to CIDs
 * in the CIDFont.
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_pdf_resource_font_cid_Font extends Zend_Pdf_Resource_Font
{
    /**
     * Object representing the font's cmap (character to glyph map).
     * @var Zend_Pdf_Cmap
     */
    protected $_cmap;
    /**
     * Array containing the widths of each character that have entries in used character map.
     *
     * @var array
     */
    protected $_char_widths;
    /**
     * Width for characters missed in the font
     *
     * @var integer
     */
    protected $_missing_char_width = 0;
    /**
     * Object constructor
     *
     * @param Zend_Pdf_FileParser_Font_OpenType $fontParser Font parser object
     *   containing OpenType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_pdf_file_Parser_font_open_Type $font_parser)
    {
        parent::__construct();
        $font_parser->parse();
        /* Object properties */
        $this->_font_names = $font_parser->names;
        $this->_is_bold = $font_parser->is_bold;
        $this->_is_italic = $font_parser->is_italic;
        $this->_is_monospace = $font_parser->is_monospaced;
        $this->_underline_position = $font_parser->underline_position;
        $this->_underline_thickness = $font_parser->underline_thickness;
        $this->_strike_position = $font_parser->strike_position;
        $this->_strike_thickness = $font_parser->strike_thickness;
        $this->_units_per_em = $font_parser->units_per_em;
        $this->_ascent = $font_parser->ascent;
        $this->_descent = $font_parser->descent;
        $this->_line_gap = $font_parser->line_gap;
        $this->_cmap = $font_parser->cmap;
        /* Resource dictionary */
        $base_font = $this->get_font_name(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
        $this->_resource->base_font = new Zend_Pdf_Element_Name($base_font);
        /**
         * Prepare widths array.
         */
        /* Constract characters widths array using font CMap and glyphs widths array */
        $glyph_widths = $font_parser->glyph_widths;
        $char_glyphs = $this->_cmap->get_covered_characters_glyphs();
        $char_widths = [];
        foreach ($char_glyphs as $char_code => $glyph) {
            if (isset($glyph_widths[$glyph]) && !is_null($glyph_widths[$glyph])) {
                $char_widths[$char_code] = $glyph_widths[$glyph];
            }
        }
        $this->_char_widths = $char_widths;
        $this->_missing_char_width = $glyph_widths[0];
        /* Width array optimization. Step1: extract default value */
        $width_frequencies = array_count_values($char_widths);
        $default_width = null;
        $default_width_frequency = -1;
        foreach ($width_frequencies as $width => $frequency) {
            if ($frequency > $default_width_frequency) {
                $default_width = $width;
                $default_width_frequency = $frequency;
            }
        }
        // Store default value in the font dictionary
        $this->_resource->DW = new Zend_Pdf_Element_Numeric($this->to_em_space($default_width));
        // Remove characters which corresponds to default width from the widths array
        $def_width_chars = array_keys($char_widths, $default_width);
        foreach ($def_width_chars as $char_code) {
            unset($char_widths[$char_code]);
        }
        // Order cheracter widths aray by character codes
        ksort($char_widths, SORT_NUMERIC);
        /* Width array optimization. Step2: Compact character codes sequences */
        $last_char_code = -1;
        $widths_sequences = [];
        foreach ($char_widths as $char_code => $width) {
            if ($last_char_code == -1) {
                $char_codes_sequense = [];
                $sequence_start_code = $char_code;
            } elseif ($char_code != $last_char_code + 1) {
                // New chracters sequence detected
                $widths_sequences[$sequence_start_code] = $char_codes_sequense;
                $char_codes_sequense = [];
                $sequence_start_code = $char_code;
            }
            $char_codes_sequense[] = $width;
            $last_char_code = $char_code;
        }
        // Save last sequence, if widths array is not empty (it may happens for monospaced fonts)
        if (count($char_widths) != 0) {
            $widths_sequences[$sequence_start_code] = $char_codes_sequense;
        }
        $pdf_chars_widths = [];
        foreach ($widths_sequences as $start_code => $widths_sequence) {
            /* Width array optimization. Step3: Compact widths sequences */
            $pdf_widths = [];
            $last_width = -1;
            $widths_in_sequence = 0;
            foreach ($widths_sequence as $width) {
                if ($last_width != $width) {
                    // New width is detected
                    if ($widths_in_sequence != 0) {
                        // Previous width value was a part of the widths sequence. Save it as 'c_1st c_last w'.
                        $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code);
                        // First character code
                        $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code + $widths_in_sequence - 1);
                        // Last character code
                        $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($this->to_em_space($last_width));
                        // Width
                        // Reset widths sequence
                        $start_code = $start_code + $widths_in_sequence;
                        $widths_in_sequence = 0;
                    }
                    // Collect new width
                    $pdf_widths[] = new Zend_Pdf_Element_Numeric($this->to_em_space($width));
                    $last_width = $width;
                } else if (count($pdf_widths) != 0) {
                    // We already have some widths collected
                    // So, we've just detected new widths sequence
                    // Remove last element from widths list, since it's a part of widths sequence
                    array_pop($pdf_widths);
                    // and write the rest if it's not empty
                    if (count($pdf_widths) != 0) {
                        // Save it as 'c_1st [w1 w2 ... wn]'.
                        $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code);
                        // First character code
                        $pdf_chars_widths[] = new Zend_Pdf_Element_Array($pdf_widths);
                        // Widths array
                        // Reset widths collection
                        $start_code += count($pdf_widths);
                        $pdf_widths = [];
                    }
                    $widths_in_sequence = 2;
                } else {
                    // Continue widths sequence
                    $widths_in_sequence++;
                }
            }
            // Check if we have widths collection or widths sequence to wite it down
            if (count($pdf_widths) != 0) {
                // We have some widths collected
                // Save it as 'c_1st [w1 w2 ... wn]'.
                $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code);
                // First character code
                $pdf_chars_widths[] = new Zend_Pdf_Element_Array($pdf_widths);
                // Widths array
            } elseif ($widths_in_sequence != 0) {
                // We have widths sequence
                // Save it as 'c_1st c_last w'.
                $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code);
                // First character code
                $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($start_code + $widths_in_sequence - 1);
                // Last character code
                $pdf_chars_widths[] = new Zend_Pdf_Element_Numeric($this->to_em_space($last_width));
                // Width
            }
        }
        /* Create the Zend_Pdf_Element_Array object and add it to the font's
         * object factory and resource dictionary.
         */
        $widths_array_element = new Zend_Pdf_Element_Array($pdf_chars_widths);
        $widths_object = $this->_object_factory->new_object($widths_array_element);
        $this->_resource->W = $widths_object;
        /* CIDSystemInfo dictionary */
        $cid_system_info = new Zend_Pdf_Element_Dictionary();
        $cid_system_info->Registry = new Zend_Pdf_Element_String('Adobe');
        $cid_system_info->Ordering = new Zend_Pdf_Element_String('UCS');
        $cid_system_info->Supplement = new Zend_Pdf_Element_Numeric(0);
        $cid_system_info_object = $this->_object_factory->new_object($cid_system_info);
        $this->_resource->cid_system_info = $cid_system_info_object;
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
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
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
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
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
        /* Convert the string to UTF-16BE encoding so we can match the string's
         * character codes to those found in the cmap.
         */
        if ($char_encoding != 'UTF-16BE') {
            $string = iconv($char_encoding, 'UTF-16BE', $string);
        }
        $char_count = iconv_strlen($string, 'UTF-16BE');
        if ($char_count == 0) {
            return 0;
        }
        /* Calculate the score by doing a lookup for each character.
         */
        $score = 0;
        $max_index = strlen($string);
        for ($i = 0; $i < $max_index; $i++) {
            /**
             * @todo Properly handle characters encoded as surrogate pairs.
             */
            $char_code = ord($string[$i]) << 8 | ord($string[++$i]);
            /* This could probably be optimized a bit with a binary search...
             */
            if (isset($this->_char_widths[$char_code])) {
                $score++;
            }
        }
        return $score / $char_count;
    }
    /**
     * Returns the widths of the Chars.
     *
     * The widths are expressed in the font's glyph space. You are responsible
     * for converting to user space as necessary. See {@link unitsPerEm()}.
     *
     * See also {@link widthForChar()}.
     *
     * @param array &$glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     */
    public function widths_for_chars($char_codes)
    {
        $widths = [];
        foreach ($char_codes as $key => $char_code) {
            if (!isset($this->_char_widths[$char_code])) {
                $widths[$key] = $this->_missing_char_width;
            } else {
                $widths[$key] = $this->_char_widths[$char_code];
            }
        }
        return $widths;
    }
    /**
     * Returns the width of the character.
     *
     * Like {@link widthsForChars()} but used for one char at a time.
     *
     * @param integer $charCode
     * @return integer
     */
    public function width_for_char($char_code)
    {
        if (!isset($this->_char_widths[$char_code])) {
            return $this->_missing_char_width;
        }
        return $this->_char_widths[$char_code];
    }
    /**
     * Returns the widths of the glyphs.
     *
     * @param array &$glyphNumbers Array of glyph numbers.
     * @return array Array of glyph widths (integers).
     * @throws Zend_Pdf_Exception
     */
    public function widths_for_glyphs($glyph_numbers)
    {
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
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
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
    }
    /**
     * Convert string to the font encoding.
     *
     * @param string $string
     * @param string $charEncoding Character encoding of source text.
     * @return string
     * @throws Zend_Pdf_Exception
     *      */
    public function encode_string($string, $char_encoding)
    {
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
    }
    /**
     * Convert string from the font encoding.
     *
     * @param string $string
     * @param string $charEncoding Character encoding of resulting text.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function decode_string($string, $char_encoding)
    {
        /**
         * CIDFont object is not actually a font. It does not have an Encoding entry,
         * it cannot be listed in the Font subdictionary of a resource dictionary, and
         * it cannot be used as the operand of the Tf operator.
         *
         * Throw an exception.
         */
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('CIDFont PDF objects could not be used as the operand of the text drawing operators');
    }
}