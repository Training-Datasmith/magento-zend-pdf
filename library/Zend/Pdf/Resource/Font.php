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
/** Zend_Pdf_Resource */
#require_once 'Zend/Pdf/Resource.php';
/**
 * Zend_Pdf_Font
 *
 * Zend_Pdf_Font class constants are used within Zend_Pdf_Resource_Font
 * and its subclusses.
 */
#require_once 'Zend/Pdf/Font.php';
/**
 * Abstract class which manages PDF fonts.
 *
 * Defines the public interface and creates shared storage for concrete
 * subclasses which are responsible for generating the font's information
 * dictionaries, mapping characters to glyphs, and providing both overall font
 * and glyph-specific metric data.
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Resource_Font extends Zend_Pdf_Resource
{
    /**** Instance Variables ****/
    /**
     * The type of font. Use TYPE_ constants defined in {@link Zend_Pdf_Font}.
     * @var integer
     */
    protected $_font_type = Zend_Pdf_Font::TYPE_UNKNOWN;
    /**
     * Array containing descriptive names for the font. See {@link fontName()}.
     * @var array
     */
    protected $_font_names = [];
    /**
     * Flag indicating whether or not this font is bold.
     * @var boolean
     */
    protected $_is_bold = false;
    /**
     * Flag indicating whether or not this font is italic.
     * @var boolean
     */
    protected $_is_italic = false;
    /**
     * Flag indicating whether or not this font is monospaced.
     * @var boolean
     */
    protected $_is_monospace = false;
    /**
     * The position below the text baseline of the underline (in glyph units).
     * @var integer
     */
    protected $_underline_position = 0;
    /**
     * The thickness of the underline (in glyph units).
     * @var integer
     */
    protected $_underline_thickness = 0;
    /**
     * The position above the text baseline of the strikethrough (in glyph units).
     * @var integer
     */
    protected $_strike_position = 0;
    /**
     * The thickness of the strikethrough (in glyph units).
     * @var integer
     */
    protected $_strike_thickness = 0;
    /**
     * Number of glyph units per em. See {@link getUnitsPerEm()}.
     * @var integer
     */
    protected $_units_per_em = 0;
    /**
     * Typographical ascent. See {@link getAscent()}.
     * @var integer
     */
    protected $_ascent = 0;
    /**
     * Typographical descent. See {@link getDescent()}.
     * @var integer
     */
    protected $_descent = 0;
    /**
     * Typographical line gap. See {@link getLineGap()}.
     * @var integer
     */
    protected $_line_gap = 0;
    /**** Public Interface ****/
    /* Object Lifecycle */
    /**
     * Object constructor.
     *
     */
    public function __construct()
    {
        parent::__construct(new Zend_Pdf_Element_Dictionary());
        $this->_resource->Type = new Zend_Pdf_Element_Name('Font');
    }
    /* Object Magic Methods */
    /**
     * Returns the full name of the font in the encoding method of the current
     * locale. Transliterates any characters that cannot be naturally
     * represented in that character set.
     */
    public function __toString(): string
    {
        return $this->get_font_name(Zend_Pdf_Font::NAME_FULL, '', '//TRANSLIT');
    }
    /* Accessors */
    /**
     * Returns the type of font.
     *
     * @return integer One of the TYPE_ constants defined in
     *   {@link Zend_Pdf_Font}.
     */
    public function get_font_type()
    {
        return $this->_font_type;
    }
    /**
     * Returns the specified descriptive name for the font.
     *
     * The font name type is usually one of the following:
     * <ul>
     *  <li>{@link Zend_Pdf_Font::NAME_FULL}
     *  <li>{@link Zend_Pdf_Font::NAME_FAMILY}
     *  <li>{@link Zend_Pdf_Font::NAME_PREFERRED_FAMILY}
     *  <li>{@link Zend_Pdf_Font::NAME_STYLE}
     *  <li>{@link Zend_Pdf_Font::NAME_PREFERRED_STYLE}
     *  <li>{@link Zend_Pdf_Font::NAME_DESCRIPTION}
     *  <li>{@link Zend_Pdf_Font::NAME_SAMPLE_TEXT}
     *  <li>{@link Zend_Pdf_Font::NAME_ID}
     *  <li>{@link Zend_Pdf_Font::NAME_VERSION}
     *  <li>{@link Zend_Pdf_Font::NAME_POSTSCRIPT}
     *  <li>{@link Zend_Pdf_Font::NAME_CID_NAME}
     *  <li>{@link Zend_Pdf_Font::NAME_DESIGNER}
     *  <li>{@link Zend_Pdf_Font::NAME_DESIGNER_URL}
     *  <li>{@link Zend_Pdf_Font::NAME_MANUFACTURER}
     *  <li>{@link Zend_Pdf_Font::NAME_VENDOR_URL}
     *  <li>{@link Zend_Pdf_Font::NAME_COPYRIGHT}
     *  <li>{@link Zend_Pdf_Font::NAME_TRADEMARK}
     *  <li>{@link Zend_Pdf_Font::NAME_LICENSE}
     *  <li>{@link Zend_Pdf_Font::NAME_LICENSE_URL}
     * </ul>
     *
     * Note that not all names are available for all fonts. In addition, some
     * fonts may contain additional names, whose indicies are in the range
     * 256 to 32767 inclusive, which are used for certain font layout features.
     *
     * If the preferred language translation is not available, uses the first
     * available translation for the name, which is usually English.
     *
     * If the requested name does not exist, returns null.
     *
     * All names are stored internally as Unicode strings, using UTF-16BE
     * encoding. You may optionally supply a different resulting character set.
     *
     * @param integer $nameType Type of name requested.
     * @param mixed $language Preferred language (string) or array of languages
     *   in preferred order. Use the ISO 639 standard 2-letter language codes.
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()};
     * @return string
     */
    public function get_font_name($name_type, $language, $character_set = null)
    {
        if (!isset($this->_font_names[$name_type])) {
            return null;
        }
        $name = null;
        if (is_array($language)) {
            foreach ($language as $code) {
                if (isset($this->_font_names[$name_type][$code])) {
                    $name = $this->_font_names[$name_type][$code];
                    break;
                }
            }
        } else if (isset($this->_font_names[$name_type][$language])) {
            $name = $this->_font_names[$name_type][$language];
        }
        /* If the preferred language could not be found, use whatever is first.
         */
        if ($name === null) {
            $names = $this->_font_names[$name_type];
            $name = reset($names);
        }
        /* Convert the character set if requested.
         */
        if ($character_set !== null && $character_set != 'UTF-16BE' && PHP_OS != 'AIX') {
            // AIX knows not this charset
            return iconv('UTF-16BE', $character_set, $name);
        }
        return $name;
    }
    /**
     * Returns whole set of font names.
     *
     * @return array
     */
    public function get_font_names()
    {
        return $this->_font_names;
    }
    /**
     * Returns true if font is bold.
     *
     * @return boolean
     */
    public function is_bold()
    {
        return $this->_is_bold;
    }
    /**
     * Returns true if font is italic.
     *
     * @return boolean
     */
    public function is_italic()
    {
        return $this->_is_italic;
    }
    /**
     * Returns true if font is monospace.
     *
     * @return boolean
     */
    public function is_monospace()
    {
        return $this->_is_monospace;
    }
    /**
     * Returns the suggested position below the text baseline of the underline
     * in glyph units.
     *
     * This value is usually negative.
     *
     * @return integer
     */
    public function get_underline_position()
    {
        return $this->_underline_position;
    }
    /**
     * Returns the suggested line thickness of the underline in glyph units.
     *
     * @return integer
     */
    public function get_underline_thickness()
    {
        return $this->_underline_thickness;
    }
    /**
     * Returns the suggested position above the text baseline of the
     * strikethrough in glyph units.
     *
     * @return integer
     */
    public function get_strike_position()
    {
        return $this->_strike_position;
    }
    /**
     * Returns the suggested line thickness of the strikethrough in glyph units.
     *
     * @return integer
     */
    public function get_strike_thickness()
    {
        return $this->_strike_thickness;
    }
    /**
     * Returns the number of glyph units per em.
     *
     * Used to convert glyph space to user space. Frequently used in conjunction
     * with {@link widthsForGlyphs()} to calculate the with of a run of text.
     *
     * @return integer
     */
    public function get_units_per_em()
    {
        return $this->_units_per_em;
    }
    /**
     * Returns the typographic ascent in font glyph units.
     *
     * The typographic ascent is the distance from the font's baseline to the
     * top of the text frame. It is frequently used to locate the initial
     * baseline for a paragraph of text inside a given rectangle.
     *
     * @return integer
     */
    public function get_ascent()
    {
        return $this->_ascent;
    }
    /**
     * Returns the typographic descent in font glyph units.
     *
     * The typographic descent is the distance below the font's baseline to the
     * bottom of the text frame. It is always negative.
     *
     * @return integer
     */
    public function get_descent()
    {
        return $this->_descent;
    }
    /**
     * Returns the typographic line gap in font glyph units.
     *
     * The typographic line gap is the distance between the bottom of the text
     * frame of one line to the top of the text frame of the next. It is
     * typically combined with the typographical ascent and descent to determine
     * the font's total line height (or leading).
     *
     * @return integer
     */
    public function get_line_gap()
    {
        return $this->_line_gap;
    }
    /**
     * Returns the suggested line height (or leading) in font glyph units.
     *
     * This value is determined by adding together the values of the typographic
     * ascent, descent, and line gap. This value yields the suggested line
     * spacing as determined by the font developer.
     *
     * It should be noted that this is only a guideline; layout engines will
     * frequently modify this value to achieve special effects such as double-
     * spacing.
     *
     * @return integer
     */
    public function get_line_height()
    {
        return $this->_ascent - $this->_descent + $this->_line_gap;
    }
    /* Information and Conversion Methods */
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
    abstract public function glyph_numbers_for_characters($character_codes);
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
    abstract public function glyph_number_for_character($character_code);
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
    abstract public function get_covered_percentage($string, $char_encoding = '');
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
    abstract public function widths_for_glyphs($glyph_numbers);
    /**
     * Returns the width of the glyph.
     *
     * Like {@link widthsForGlyphs()} but used for one glyph at a time.
     *
     * @param integer $glyphNumber
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    abstract public function width_for_glyph($glyph_number);
    /**
     * Convert string to the font encoding.
     *
     * The method is used to prepare string for text drawing operators
     *
     * @param string $string
     * @param string $charEncoding Character encoding of source text.
     * @return string
     */
    abstract public function encode_string($string, $char_encoding);
    /**
     * Convert string from the font encoding.
     *
     * The method is used to convert strings retrieved from existing content streams
     *
     * @param string $string
     * @param string $charEncoding Character encoding of resulting text.
     * @return string
     */
    abstract public function decode_string($string, $char_encoding);
    /**** Internal Methods ****/
    /**
     * If the font's glyph space is not 1000 units per em, converts the value.
     *
     * @internal
     * @param integer $value
     * @return integer
     */
    public function to_em_space($value)
    {
        if ($this->_units_per_em == 1000) {
            return $value;
        }
        return ceil($value / $this->_units_per_em * 1000);
        // always round up
    }
}