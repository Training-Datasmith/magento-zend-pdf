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
 * Implements the "byte encoding" character map (type 0).
 *
 * This is the (legacy) Apple standard encoding mechanism and provides coverage
 * for characters in the Mac Roman character set only. Consequently, this cmap
 * type should be used only as a last resort.
 *
 * The mapping from Mac Roman to Unicode can be found at
 * {@link http://www.unicode.org/Public/MAPPINGS/VENDORS/APPLE/ROMAN.TXT}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_cmap_byte_Encoding extends Zend_Pdf_Cmap
{
    /**** Instance Variables ****/
    /**
     * Glyph index array. Stores the actual glyph numbers. The array keys are
     * the translated Unicode code points.
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
            if (!isset($this->_glyph_index_array[$character_code])) {
                $glyph_numbers[$key] = Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
                continue;
            }
            $glyph_numbers[$key] = $this->_glyph_index_array[$character_code];
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
        if (!isset($this->_glyph_index_array[$character_code])) {
            return Zend_Pdf_Cmap::MISSING_CHARACTER_GLYPH;
        }
        return $this->_glyph_index_array[$character_code];
    }
    /**
     * Returns an array containing the Unicode characters that have entries in
     * this character map.
     *
     * @return array Unicode character codes.
     */
    public function get_covered_characters(): array
    {
        return array_keys($this->_glyph_index_array);
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
    public function get_covered_characters_glyphs()
    {
        return $this->_glyph_index_array;
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
        /* Sanity check: This table must be exactly 262 bytes long.
         */
        $actual_length = strlen($cmap_data);
        if ($actual_length != 262) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Insufficient table data', Zend_Pdf_Exception::CMAP_TABLE_DATA_TOO_SMALL);
        }
        /* Sanity check: Make sure this is right data for this table type.
         */
        $type = $this->_extract_u_int2($cmap_data, 0);
        if ($type != Zend_Pdf_Cmap::TYPE_BYTE_ENCODING) {
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
        /* The mapping between the Mac Roman and Unicode characters is static.
         * For simplicity, just put all 256 glyph indices into one array keyed
         * off the corresponding Unicode character.
         */
        $i = 6;
        $this->_glyph_index_array[0x0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x10] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x11] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x12] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x13] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x14] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x15] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x16] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x17] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x18] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x19] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x1f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x20] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x21] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x22] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x23] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x24] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x25] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x26] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x27] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x28] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x29] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x30] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x31] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x32] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x33] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x34] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x35] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x36] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x37] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x38] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x39] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x40] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x41] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x42] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x43] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x44] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x45] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x46] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x47] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x48] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x49] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x4f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x50] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x51] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x52] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x53] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x54] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x55] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x56] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x57] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x58] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x59] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x5f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x60] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x61] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x62] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x63] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x64] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x65] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x66] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x67] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x68] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x69] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x6f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x70] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x71] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x72] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x73] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x74] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x75] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x76] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x77] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x78] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x79] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x7f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xdc] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xea] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xeb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xed] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xec] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xee] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xef] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xfa] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xfb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xfc] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2020] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2022] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xdf] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xae] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2122] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2260] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x221e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2264] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2265] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2202] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2211] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x220f] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3c0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x222b] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xaa] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xba] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x3a9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xe6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xbf] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xac] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x221a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x192] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2248] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2206] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xab] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xbb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2026] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xa0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc0] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd5] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x152] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x153] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2013] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2014] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x201c] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x201d] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2018] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2019] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x25ca] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xff] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x178] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2044] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x20ac] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2039] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x203a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xfb01] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xfb02] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2021] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb7] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x201a] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x201e] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2030] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xca] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc1] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xcb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xc8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xcd] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xce] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xcf] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xcc] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd3] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd4] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xf8ff] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd2] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xda] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xdb] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xd9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x131] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2c6] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2dc] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xaf] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2d8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2d9] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2da] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0xb8] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2dd] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2db] = ord($cmap_data[$i++]);
        $this->_glyph_index_array[0x2c7] = ord($cmap_data[$i]);
    }
}