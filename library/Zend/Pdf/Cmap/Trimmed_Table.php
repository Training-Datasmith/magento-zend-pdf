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
/** Zend_Pdf_Cmap */
#require_once 'Zend/Pdf/Cmap.php';
/**
 * Implements the "trimmed table mapping" character map (type 6).
 *
 * This table type is preferred over the {@link Zend_Pdf_Cmap_SegmentToDelta}
 * table when the Unicode characters covered by the font fall into a single
 * contiguous range.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_cmap_trimmed_Table extends Zend_Pdf_Cmap
{
    /**** Instance Variables ****/
    /**
     * The starting character code covered by this table.
     * @var integer
     */
    protected $_start_code = 0;
    /**
     * The ending character code covered by this table.
     * @var integer
     */
    protected $_end_code = 0;
    /**
     * Glyph index array. Stores the actual glyph numbers.
     * @var array
     */
    protected $_glyph_index_array = [];
    /**** Public Interface ****/
    /* Concrete Class Implementation */
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
    public function glyph_numbers_for_characters($character_codes): array
    {
        $glyph_numbers = [];
        foreach ($character_codes as $key => $character_code) {
            if ($character_code < $this->_start_code || $character_code > $this->_end_code) {
                $glyph_numbers[$key] = Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
                continue;
            }
            $glyph_index = $character_code - $this->_start_code;
            $glyph_numbers[$key] = $this->_glyph_index_array[$glyph_index];
        }
        return $glyph_numbers;
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
        if ($character_code < $this->_start_code || $character_code > $this->_end_code) {
            return Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
        }
        $glyph_index = $character_code - $this->_start_code;
        return $this->_glyph_index_array[$glyph_index];
    }
    /**
     * Returns an array containing the Unicode characters that have entries in
     * this character map.
     *
     * @return array Unicode character codes.
     */
    public function get_covered_characters(): array
    {
        $character_codes = [];
        for ($code = $this->_start_code; $code <= $this->_end_code; $code++) {
            $character_codes[] = $code;
        }
        return $character_codes;
    }
    /**
     * Returns an array containing the glyphs numbers that have entries in this character map.
     * Keys are Unicode character codes (integers)
     *
     * This functionality is partially covered by glyphNumbersForCharacters(getCoveredCharacters())
     * call, but this method do it in more effective way (prepare complete list instead of searching
     * glyph for each character code).
     *
     * @internal
     * @return array Array representing <Unicode character code> => <glyph number> pairs.
     */
    public function get_covered_characters_glyphs(): array
    {
        $glyph_numbers = [];
        for ($code = $this->_start_code; $code <= $this->_end_code; $code++) {
            $glyph_numbers[$code] = $this->_glyph_index_array[$code - $this->_start_code];
        }
        return $glyph_numbers;
    }
    /* Object Lifecycle */
    /**
     * Object constructor
     *
     * Parses the raw binary table data. Throws an exception if the table is
     * malformed.
     *
     * @param string $cmapData Raw binary cmap table data.
     * @throws Zend_Pdf_Exception
     */
    public function __construct($cmap_data)
    {
        /* Sanity check: The table should be at least 9 bytes in size.
         */
        $actual_length = strlen($cmap_data);
        if ($actual_length < 9) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Insufficient table data', Zend_Pdf_Exception::CMAP_TABLE_DATA_TOO_SMALL);
        }
        /* Sanity check: Make sure this is right data for this table type.
         */
        $type = $this->_extract_u_int2($cmap_data, 0);
        if ($type != Zend_Pdf_Cmap::TYPE_TRIMMED_TABLE) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Wrong cmap table type', Zend_Pdf_Exception::CMAP_WRONG_TABLE_TYPE);
        }
        $length = $this->_extract_u_int2($cmap_data, 2);
        if ($length != $actual_length) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Table length ({$length}) does not match actual length ({$actual_length})", Zend_Pdf_Exception::CMAP_WRONG_TABLE_LENGTH);
        }
        /* Mapping tables should be language-independent. The font may not work
         * as expected if they are not. Unfortunately, many font files in the
         * wild incorrectly record a language ID in this field, so we can't
         * call this a failure.
         */
        $language = $this->_extract_u_int2($cmap_data, 4);
        if ($language != 0) {
            // Record a warning here somehow?
        }
        $this->_start_code = $this->_extract_u_int2($cmap_data, 6);
        $entry_count = $this->_extract_u_int2($cmap_data, 8);
        $expected_count = $length - 10 >> 1;
        if ($entry_count != $expected_count) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Entry count is wrong; expected: {$expected_count}; actual: {$entry_count}", Zend_Pdf_Exception::CMAP_WRONG_ENTRY_COUNT);
        }
        $this->_end_code = $this->_start_code + $entry_count - 1;
        $offset = 10;
        for ($i = 0; $i < $entry_count; $i++, $offset += 2) {
            $this->_glyph_index_array[] = $this->_extract_u_int2($cmap_data, $offset);
        }
        /* Sanity check: After reading all of the data, we should be at the end
         * of the table.
         */
        if ($offset != $length) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Ending offset ({$offset}) does not match length ({$length})", Zend_Pdf_Exception::CMAP_FINAL_OFFSET_NOT_LENGTH);
        }
    }
}