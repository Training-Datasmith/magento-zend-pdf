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
 * Implements the "segment mapping to delta values" character map (type 4).
 *
 * This is the Microsoft standard mapping table type for OpenType fonts. It
 * provides the ability to cover multiple contiguous ranges of the Unicode
 * character set, with the exception of Unicode Surrogates (U+D800 - U+DFFF).
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_cmap_segment_To_Delta extends Zend_Pdf_Cmap
{
    /**** Instance Variables ****/
    /**
     * The number of segments in the table.
     * @var integer
     */
    protected $_segment_count = 0;
    /**
     * The size of the binary search range for segments.
     * @var integer
     */
    protected $_search_range = 0;
    /**
     * The number of binary search steps required to cover the entire search
     * range.
     * @var integer
     */
    protected $_search_iterations = 0;
    /**
     * Array of ending character codes for each segment.
     * @var array
     */
    protected $_segment_table_end_codes = [];
    /**
     * The ending character code for the segment at the end of the low search
     * range.
     * @var integer
     */
    protected $_search_range_end_code = 0;
    /**
     * Array of starting character codes for each segment.
     * @var array
     */
    protected $_segment_table_start_codes = [];
    /**
     * Array of character code to glyph delta values for each segment.
     * @var array
     */
    protected $_segment_table_id_deltas = [];
    /**
     * Array of offsets into the glyph index array for each segment.
     * @var array
     */
    protected $_segment_table_id_range_offsets = [];
    /**
     * Glyph index array. Stores glyph numbers, used with range offset.
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
            /* These tables only cover the 16-bit character range.
             */
            if ($character_code > 0xffff) {
                $glyph_numbers[$key] = Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
                continue;
            }
            /* Determine where to start the binary search. The segments are
             * ordered from lowest-to-highest. We are looking for the first
             * segment whose end code is greater than or equal to our character
             * code.
             *
             * If the end code at the top of the search range is larger, then
             * our target is probably below it.
             *
             * If it is smaller, our target is probably above it, so move the
             * search range to the end of the segment list.
             */
            if ($this->_search_range_end_code >= $character_code) {
                $search_index = $this->_search_range;
            } else {
                $search_index = $this->_segment_count;
            }
            /* Now do a binary search to find the first segment whose end code
             * is greater or equal to our character code. No matter the number
             * of segments (there may be hundreds in a large font), we will only
             * need to perform $this->_searchIterations.
             */
            for ($i = 1; $i <= $this->_search_iterations; $i++) {
                if ($this->_segment_table_end_codes[$search_index] >= $character_code) {
                    $subtable_index = $search_index;
                    $search_index -= $this->_search_range >> $i;
                } else {
                    $search_index += $this->_search_range >> $i;
                }
            }
            /* If the segment's start code is greater than our character code,
             * that character is not represented in this font. Move on.
             */
            if ($this->_segment_table_start_codes[$subtable_index] > $character_code) {
                $glyph_numbers[$key] = Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
                continue;
            }
            if ($this->_segment_table_id_range_offsets[$subtable_index] == 0) {
                /* This segment uses a simple mapping from character code to
                 * glyph number.
                 */
                $glyph_numbers[$key] = ($character_code + $this->_segment_table_id_deltas[$subtable_index]) % 65536;
            } else {
                /* This segment relies on the glyph index array to determine the
                 * glyph number. The calculation below determines the correct
                 * index into that array. It's a little odd because the range
                 * offset in the font file is designed to quickly provide an
                 * address of the index in the raw binary data instead of the
                 * index itself. Since we've parsed the data into arrays, we
                 * must process it a bit differently.
                 */
                $glyph_index = $character_code - $this->_segment_table_start_codes[$subtable_index] + $this->_segment_table_id_range_offsets[$subtable_index] - $this->_segment_count + $subtable_index - 1;
                $glyph_numbers[$key] = $this->_glyph_index_array[$glyph_index];
            }
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
        /* This code is pretty much a copy of glyphNumbersForCharacters().
         * See that method for inline documentation.
         */
        if ($character_code > 0xffff) {
            return Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
        }
        if ($this->_search_range_end_code >= $character_code) {
            $search_index = $this->_search_range;
        } else {
            $search_index = $this->_segment_count;
        }
        for ($i = 1; $i <= $this->_search_iterations; $i++) {
            if ($this->_segment_table_end_codes[$search_index] >= $character_code) {
                $subtable_index = $search_index;
                $search_index -= $this->_search_range >> $i;
            } else {
                $search_index += $this->_search_range >> $i;
            }
        }
        if ($this->_segment_table_start_codes[$subtable_index] > $character_code) {
            return Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
        }
        if ($this->_segment_table_id_range_offsets[$subtable_index] == 0) {
            return ($character_code + $this->_segment_table_id_deltas[$subtable_index]) % 65536;
        }
        $glyph_index = $character_code - $this->_segment_table_start_codes[$subtable_index] + $this->_segment_table_id_range_offsets[$subtable_index] - $this->_segment_count + $subtable_index - 1;
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
        for ($i = 1; $i <= $this->_segment_count; $i++) {
            for ($code = $this->_segment_table_start_codes[$i]; $code <= $this->_segment_table_end_codes[$i]; $code++) {
                $character_codes[] = $code;
            }
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
        for ($segment_num = 1; $segment_num <= $this->_segment_count; $segment_num++) {
            if ($this->_segment_table_id_range_offsets[$segment_num] == 0) {
                $delta = $this->_segment_table_id_deltas[$segment_num];
                for ($code = $this->_segment_table_start_codes[$segment_num]; $code <= $this->_segment_table_end_codes[$segment_num]; $code++) {
                    $glyph_numbers[$code] = ($code + $delta) % 65536;
                }
            } else {
                $code = $this->_segment_table_start_codes[$segment_num];
                $glyph_index = $this->_segment_table_id_range_offsets[$segment_num] - ($this->_segment_count - $segment_num) - 1;
                while ($code <= $this->_segment_table_end_codes[$segment_num]) {
                    $glyph_numbers[$code] = $this->_glyph_index_array[$glyph_index];
                    $code++;
                    $glyph_index++;
                }
            }
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
        /* Sanity check: The table should be at least 23 bytes in size.
         */
        $actual_length = strlen($cmap_data);
        if ($actual_length < 23) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Insufficient table data', Zend_Pdf_Exception::CMAP_TABLE_DATA_TOO_SMALL);
        }
        /* Sanity check: Make sure this is right data for this table type.
         */
        $type = $this->_extract_u_int2($cmap_data, 0);
        if ($type != Zend_Pdf_Cmap::TYPE_SEGMENT_TO_DELTA) {
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
        /* These two values are stored premultiplied by two which is convienent
         * when using the binary data directly, but we're parsing it out to
         * native PHP data types, so divide by two.
         */
        $this->_segment_count = $this->_extract_u_int2($cmap_data, 6) >> 1;
        $this->_search_range = $this->_extract_u_int2($cmap_data, 8) >> 1;
        $this->_search_iterations = $this->_extract_u_int2($cmap_data, 10) + 1;
        $offset = 14;
        for ($i = 1; $i <= $this->_segment_count; $i++, $offset += 2) {
            $this->_segment_table_end_codes[$i] = $this->_extract_u_int2($cmap_data, $offset);
        }
        $this->_search_range_end_code = $this->_segment_table_end_codes[$this->_search_range];
        $offset += 2;
        // reserved bytes
        for ($i = 1; $i <= $this->_segment_count; $i++, $offset += 2) {
            $this->_segment_table_start_codes[$i] = $this->_extract_u_int2($cmap_data, $offset);
        }
        for ($i = 1; $i <= $this->_segment_count; $i++, $offset += 2) {
            $this->_segment_table_id_deltas[$i] = $this->_extract_int2($cmap_data, $offset);
            // signed
        }
        /* The range offset helps determine the index into the glyph index array.
         * Like the segment count and search range above, it's stored as a byte
         * multiple in the font, so divide by two as we extract the values.
         */
        for ($i = 1; $i <= $this->_segment_count; $i++, $offset += 2) {
            $this->_segment_table_id_range_offsets[$i] = $this->_extract_u_int2($cmap_data, $offset) >> 1;
        }
        /* The size of the glyph index array varies by font and depends on the
         * extent of the usage of range offsets versus deltas. Some fonts may
         * not have any entries in this array.
         */
        for (; $offset < $length; $offset += 2) {
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