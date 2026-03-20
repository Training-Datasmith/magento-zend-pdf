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
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Zend_Pdf_FileParser_Font */
#require_once 'Zend/Pdf/FileParser/Font.php';
/**
 * Abstract base class for OpenType font file parsers.
 *
 * TrueType was originally developed by Apple and was adopted as the default
 * font format for the Microsoft Windows platform. OpenType is an extension of
 * TrueType, developed jointly by Microsoft and Adobe, which adds support for
 * PostScript font data.
 *
 * This abstract parser class forms the foundation for concrete subclasses which
 * extract either TrueType or PostScript font data from the file.
 *
 * All OpenType files use big-endian byte ordering.
 *
 * The full TrueType and OpenType specifications can be found at:
 * <ul>
 *  <li>{@link http://developer.apple.com/textfonts/TTRefMan/}
 *  <li>{@link http://www.microsoft.com/typography/OTSPEC/}
 *  <li>{@link http://partners.adobe.com/public/developer/opentype/index_spec.html}
 * </ul>
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_pdf_file_Parser_font_open_Type extends Zend_pdf_file_Parser_font
{
    /**** Instance Variables ****/
    /**
     * Stores the scaler type (font type) for the font file. See
     * {@link _readScalerType()}.
     * @var integer
     */
    protected $_scaler_type = 0;
    /**
     * Stores the byte offsets to the various information tables.
     * @var array
     */
    protected $_table_directory = [];
    /**** Public Interface ****/
    /* Semi-Concrete Class Implementation */
    /**
     * Verifies that the font file is in the expected format.
     *
     * NOTE: This method should be overridden in subclasses to check the
     * specific format and set $this->_isScreened!
     *
     * @throws Zend_Pdf_Exception
     */
    public function screen()
    {
        if ($this->_is_screened) {
            return;
        }
        $this->_read_scaler_type();
    }
    /**
     * Reads and parses the font data from the file on disk.
     *
     * NOTE: This method should be overridden in subclasses to add type-
     * specific parsing and set $this->isParsed.
     *
     * @throws Zend_Pdf_Exception
     */
    public function parse()
    {
        if ($this->_is_parsed) {
            return;
        }
        /* Screen the font file first, if it hasn't been done yet.
         */
        $this->screen();
        /* Start by reading the table directory.
         */
        $this->_parse_table_directory();
        /* Then parse all of the required tables.
         */
        $this->_parse_head_table();
        $this->_parse_name_table();
        $this->_parse_post_table();
        $this->_parse_hhea_table();
        $this->_parse_maxp_table();
        $this->_parse_os2table();
        $this->_parse_hmtx_table();
        $this->_parse_cmap_table();
        /* If present, parse the optional tables.
         */
        /**
         * @todo Add parser for kerning pairs.
         * @todo Add parser for ligatures.
         * @todo Add parser for other useful hinting tables.
         */
    }
    /**** Internal Methods ****/
    /* Parser Methods */
    /**
     * Parses the OpenType table directory.
     *
     * The table directory contains the identifier, checksum, byte offset, and
     * length of each of the information tables housed in the font file.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_table_directory()
    {
        $this->move_to_offset(4);
        $table_count = $this->read_u_int(2);
        $this->_debug_log('%d tables', $table_count);
        /* Sanity check, in case we're not actually reading a OpenType file and
         * the first four bytes coincidentally matched an OpenType signature in
         * screen() above.
         *
         * There are at minimum 7 required tables: cmap, head, hhea, hmtx, maxp,
         * name, and post. In the current OpenType standard, only 32 table types
         * are defined, so use 50 as a practical limit.
         */
        if ($table_count < 7 || $table_count > 50) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Table count not within expected range', Zend_Pdf_Exception::BAD_TABLE_COUNT);
        }
        /* Skip the next 6 bytes, which contain values to aid a binary search.
         */
        $this->skip_bytes(6);
        /* The directory contains four values: the name of the table, checksum,
         * offset to the table from the beginning of the font, and actual data
         * length of the table.
         */
        for ($table_index = 0; $table_index < $table_count; $table_index++) {
            $table_name = $this->read_bytes(4);
            /* We ignore the checksum here for two reasons: First, the PDF viewer
             * will do this later anyway; Second, calculating the checksum would
             * require unsigned integers, which PHP does not currently provide.
             * We may revisit this in the future.
             */
            $this->skip_bytes(4);
            $table_offset = $this->read_u_int(4);
            $table_length = $this->read_u_int(4);
            $this->_debug_log('%s offset: 0x%x; length: %d', $table_name, $table_offset, $table_length);
            /* Sanity checks for offset and length values.
             */
            $file_size = $this->_data_source->get_size();
            if ($table_offset < 0 || $table_offset > $file_size) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception("Table offset ({$table_offset}) not within expected range", Zend_Pdf_Exception::INDEX_OUT_OF_RANGE);
            }
            if ($table_length < 0 || $table_offset + $table_length > $file_size) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception("Table length ({$table_length}) not within expected range", Zend_Pdf_Exception::INDEX_OUT_OF_RANGE);
            }
            $this->_table_directory[$table_name]['offset'] = $table_offset;
            $this->_table_directory[$table_name]['length'] = $table_length;
        }
    }
    /**
     * Parses the OpenType head (Font Header) table.
     *
     * The head table contains global information about the font such as the
     * revision number and global metrics.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_head_table()
    {
        $this->_jump_to_table('head');
        /* We can read any version 1 table.
         */
        $this->_read_table_version(1, 1);
        /* Skip the font revision number and checksum adjustment.
         */
        $this->skip_bytes(8);
        $magic_number = $this->read_u_int(4);
        if ($magic_number != 0x5f0f3cf5) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Wrong magic number. Expected: 0x5f0f3cf5; actual: ' . sprintf('%x', $magic_number), Zend_Pdf_Exception::BAD_MAGIC_NUMBER);
        }
        /* Most of the flags we ignore, but there are a few values that are
         * useful for our layout routines.
         */
        $flags = $this->read_u_int(2);
        $this->baseline_at_zero = $this->is_bit_set(0, $flags);
        $this->use_integer_scaling = $this->is_bit_set(3, $flags);
        $this->units_per_em = $this->read_u_int(2);
        $this->_debug_log('Units per em: %d', $this->units_per_em);
        /* Skip creation and modification date/time.
         */
        $this->skip_bytes(16);
        $this->x_min = $this->read_int(2);
        $this->y_min = $this->read_int(2);
        $this->x_max = $this->read_int(2);
        $this->y_max = $this->read_int(2);
        $this->_debug_log('Font bounding box: %d %d %d %d', $this->x_min, $this->y_min, $this->x_max, $this->y_max);
        /* The style bits here must match the fsSelection bits in the OS/2
         * table, if present.
         */
        $mac_style_bits = $this->read_u_int(2);
        $this->is_bold = $this->is_bit_set(0, $mac_style_bits);
        $this->is_italic = $this->is_bit_set(1, $mac_style_bits);
        /* We don't need the remainder of data in this table: smallest readable
         * size, font direction hint, indexToLocFormat, and glyphDataFormat.
         */
    }
    /**
     * Parses the OpenType name (Naming) table.
     *
     * The name table contains all of the identifying strings associated with
     * the font such as its name, copyright, trademark, license, etc.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_name_table()
    {
        $this->_jump_to_table('name');
        $base_offset = $this->_table_directory['name']['offset'];
        /* The name table begins with a short header, followed by each of the
         * fixed-length name records, followed by the variable-length strings.
         */
        /* We only understand version 0 tables.
         */
        $table_format = $this->read_u_int(2);
        if ($table_format != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unable to read format {$table_format} table", Zend_Pdf_Exception::DONT_UNDERSTAND_TABLE_VERSION);
        }
        $this->_debug_log('Format %d table', $table_format);
        $name_count = $this->read_u_int(2);
        $this->_debug_log('%d name strings', $name_count);
        $storage_offset = $this->read_u_int(2) + $base_offset;
        $this->_debug_log('Storage offset: 0x%x', $storage_offset);
        /* Scan the name records for those we're interested in. We'll skip over
         * encodings and languages we don't understand or support. Prefer the
         * Microsoft Unicode encoding for a given name/language combination, but
         * use Mac Roman if nothing else is available. We will extract the
         * actual strings later.
         */
        $name_records = [];
        for ($name_index = 0; $name_index < $name_count; $name_index++) {
            $platform_id = $this->read_u_int(2);
            $encoding_id = $this->read_u_int(2);
            if (!($platform_id == 3 && $encoding_id == 1 || $platform_id == 1 && $encoding_id == 0)) {
                $this->skip_bytes(8);
                // Not a supported encoding. Move on.
                continue;
            }
            $language_id = $this->read_u_int(2);
            $name_id = $this->read_u_int(2);
            $name_length = $this->read_u_int(2);
            $name_offset = $this->read_u_int(2);
            $language_code = $this->_language_code_for_platform($platform_id, $language_id);
            if ($language_code === null) {
                $this->_debug_log('Skipping languageID: 0x%x; platformID %d', $language_id, $platform_id);
                continue;
                // Not a supported language. Move on.
            }
            $this->_debug_log('Adding nameID: %d; languageID: 0x%x; platformID: %d; offset: 0x%x (0x%x); length: %d', $name_id, $language_id, $platform_id, $base_offset + $name_offset, $name_offset, $name_length);
            /* Entries in the name table are sorted by platform ID. If an entry
             * exists for both Mac Roman and Microsoft Unicode, the Unicode entry
             * will prevail since it is processed last.
             */
            $name_records[$name_id][$language_code] = ['platform' => $platform_id, 'offset' => $name_offset, 'length' => $name_length];
        }
        /* Now go back and extract the interesting strings.
         */
        $font_names = [];
        foreach ($name_records as $name => $languages) {
            foreach ($languages as $language => $attributes) {
                $string_offset = $storage_offset + $attributes['offset'];
                $this->move_to_offset($string_offset);
                if ($attributes['platform'] == 3) {
                    $string = $this->read_string_utf16($attributes['length']);
                } else {
                    $string = $this->read_string_mac_roman($attributes['length']);
                }
                $font_names[$name][$language] = $string;
            }
        }
        $this->names = $font_names;
    }
    /**
     * Parses the OpenType post (PostScript Information) table.
     *
     * The post table contains additional information required for using the font
     * on PostScript printers. It also contains the preferred location and
     * thickness for an underline, which is used by our layout code.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_post_table()
    {
        $this->_jump_to_table('post');
        /* We can read versions 1-4 tables.
         */
        $this->_read_table_version(1, 4);
        $this->italic_angle = $this->read_fixed(16, 16);
        $this->underline_position = $this->read_int(2);
        $this->underline_thickness = $this->read_int(2);
        $fixed_pitch = $this->read_u_int(4);
        $this->is_monospaced = $fixed_pitch !== 0;
        /* Skip over PostScript virtual memory usage.
         */
        $this->skip_bytes(16);
        /* The format of the remainder of this table is dependent on the table
         * version. However, since it contains glyph ordering information and
         * PostScript names which we don't use, move on. (This may change at
         * some point in the future though...)
         */
    }
    /**
     * Parses the OpenType hhea (Horizontal Header) table.
     *
     * The hhea table contains information used for horizontal layout. It also
     * contains some vertical layout information for Apple systems. The vertical
     * layout information for the PDF file is usually taken from the OS/2 table.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_hhea_table()
    {
        $this->_jump_to_table('hhea');
        /* We can read any version 1 table.
         */
        $this->_read_table_version(1, 1);
        /* The typographic ascent, descent, and line gap values are Apple-
         * specific. Similar values exist in the OS/2 table. We'll use these
         * values unless better values are found in OS/2.
         */
        $this->ascent = $this->read_int(2);
        $this->descent = $this->read_int(2);
        $this->line_gap = $this->read_int(2);
        /* The descent value is supposed to be negative--it's the distance
         * relative to the baseline. However, some fonts improperly store a
         * positive value in this field. If a positive value is found, flip the
         * sign and record a warning in the debug log that we did this.
         */
        if ($this->descent > 0) {
            $this->_debug_log('Warning: Font should specify negative descent. Actual: %d; Using %d', $this->descent, -$this->descent);
            $this->descent = -$this->descent;
        }
        /* Skip over advance width, left and right sidebearing, max x extent,
         * caret slope rise, run, and offset, and the four reserved fields.
         */
        $this->skip_bytes(22);
        /* These values are needed to read the hmtx table.
         */
        $this->metric_data_format = $this->read_int(2);
        $this->number_h_metrics = $this->read_u_int(2);
        $this->_debug_log('hmtx data format: %d; number of metrics: %d', $this->metric_data_format, $this->number_h_metrics);
    }
    /**
     * Parses the OpenType hhea (Horizontal Header) table.
     *
     * The hhea table contains information used for horizontal layout. It also
     * contains some vertical layout information for Apple systems. The vertical
     * layout information for the PDF file is usually taken from the OS/2 table.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_maxp_table()
    {
        $this->_jump_to_table('maxp');
        /* We don't care about table version.
         */
        $this->_read_table_version(0, 1);
        /* The number of glyphs in the font.
         */
        $this->num_glyphs = $this->read_u_int(2);
        $this->_debug_log('number of glyphs: %d', $this->num_glyphs);
        // Skip other maxp table entries (if presented with table version 1.0)...
    }
    /**
     * Parses the OpenType OS/2 (OS/2 and Windows Metrics) table.
     *
     * The OS/2 table contains additional metrics data that is required to use
     * the font on the OS/2 or Microsoft Windows platforms. It is not required
     * for Macintosh fonts, so may not always be present. When available, we use
     * this table to determine most of the vertical layout and stylistic
     * information and for the font.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_os2table()
    {
        if (!$this->number_h_metrics) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('hhea table must be parsed prior to calling this method', Zend_Pdf_Exception::PARSED_OUT_OF_ORDER);
        }
        try {
            $this->_jump_to_table('OS/2');
        } catch (Zend_Pdf_Exception $e) {
            /* This table is not always present. If missing, use default values.
             */
            #require_once 'Zend/Pdf/Exception.php';
            if ($e->get_code() == Zend_Pdf_Exception::REQUIRED_TABLE_NOT_FOUND) {
                $this->_debug_log('No OS/2 table found. Using default values');
                $this->font_weight = Zend_Pdf_Font::WEIGHT_NORMAL;
                $this->font_width = Zend_Pdf_Font::WIDTH_NORMAL;
                $this->is_embeddable = true;
                $this->is_subsettable = true;
                $this->strike_thickness = $this->units_per_em * 0.05;
                $this->strike_position = $this->units_per_em * 0.225;
                $this->is_serif_font = false;
                // the style of the font is unknown
                $this->is_sans_serif_font = false;
                $this->is_ornamental_font = false;
                $this->is_script_font = false;
                $this->is_symbolic_font = false;
                $this->is_adobe_latin_subset = false;
                $this->vendor_id = '';
                $this->x_height = 0;
                $this->capital_height = 0;
                return;
            }
            /* Something else went wrong. Throw this exception higher up the chain.
             */
            throw $e;
        }
        /* Version 0 tables are becoming rarer these days. They are only found
         * in older fonts.
         *
         * Version 1 formally defines the Unicode character range bits and adds
         * two new fields to the end of the table.
         *
         * Version 2 defines several additional flags to the embedding bits
         * (fsType field) and five new fields to the end of the table.
         *
         * Versions 2 and 3 are structurally identical. There are only two
         * significant differences between the two: First, in version 3, the
         * average character width (xAvgCharWidth field) is calculated using all
         * non-zero width glyphs in the font instead of just the Latin lower-
         * case alphabetic characters; this doesn't affect us. Second, in
         * version 3, the embedding bits (fsType field) have been made mutually
         * exclusive; see additional discusson on this below.
         *
         * We can understand all four of these table versions.
         */
        $table_version = $this->read_u_int(2);
        if ($table_version < 0 || $table_version > 3) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unable to read version {$table_version} table", Zend_Pdf_Exception::DONT_UNDERSTAND_TABLE_VERSION);
        }
        $this->_debug_log('Version %d table', $table_version);
        $this->average_char_width = $this->read_int(2);
        /* Indicates the visual weight and aspect ratio of the characters. Used
         * primarily to logically sort fonts in lists. Also used to help choose
         * a more appropriate substitute font when necessary. See the WEIGHT_
         * and WIDTH_ constants defined in Zend_Pdf_Font.
         */
        $this->font_weight = $this->read_u_int(2);
        $this->font_width = $this->read_u_int(2);
        /* Describes the font embedding licensing rights. We can only embed and
         * subset a font when given explicit permission.
         *
         * NOTE: We always interpret these bits according to the rules defined
         * in version 3 of this table, regardless of the actual version. This
         * means we will perform our checks in order from the most-restrictive
         * to the least.
         */
        $embedding_flags = $this->read_u_int(2);
        $this->_debug_log('Embedding flags: %d', $embedding_flags);
        if ($this->is_bit_set(9, $embedding_flags)) {
            /* Only bitmaps may be embedded. We don't have the ability to strip
             * outlines from fonts yet, so this means no embed.
             */
            $this->is_embeddable = false;
        } elseif ($this->is_bit_set(2, $embedding_flags) || $this->is_bit_set(3, $embedding_flags) || $this->is_bit_set(4, $embedding_flags)) {
            /* One of:
             *     Restricted License embedding (0x0002)
             *     Preview & Print embedding (0x0004)
             *     Editable embedding (0x0008)
             * is set.
             */
            $this->is_embeddable = true;
        } elseif ($this->is_bit_set(1, $embedding_flags)) {
            /* Restricted license embedding & no other embedding is set.
             * We currently don't have any way to
             * enforce this, so interpret this as no embed. This may be revised
             * in the future...
             */
            $this->is_embeddable = false;
        } else {
            /* The remainder of the bit settings grant us permission to embed
             * the font. There may be additional usage rights granted or denied
             * but those only affect the PDF viewer application, not our code.
             */
            $this->is_embeddable = true;
        }
        $this->_debug_log('Font ' . ($this->is_embeddable ? 'may' : 'may not') . ' be embedded');
        $this->is_bit_set($embedding_flags, 8);
        /* Recommended size and offset for synthesized subscript characters.
         */
        $this->subscript_x_size = $this->read_int(2);
        $this->subscript_y_size = $this->read_int(2);
        $this->subscript_x_offset = $this->read_int(2);
        $this->subscript_y_offset = $this->read_int(2);
        /* Recommended size and offset for synthesized superscript characters.
         */
        $this->superscript_x_size = $this->read_int(2);
        $this->superscript_y_size = $this->read_int(2);
        $this->superscript_x_offset = $this->read_int(2);
        $this->superscript_y_offset = $this->read_int(2);
        /* Size and vertical offset for the strikethrough.
         */
        $this->strike_thickness = $this->read_int(2);
        $this->strike_position = $this->read_int(2);
        /* Describes the class of font: serif, sans serif, script. etc. These
         * values are defined here:
         *   http://www.microsoft.com/OpenType/OTSpec/ibmfc.htm
         */
        $family_class = $this->read_u_int(2) >> 8;
        // don't care about subclass
        $this->_debug_log('Font family class: %d', $family_class);
        $this->is_serif_font = $family_class >= 1 && $family_class <= 5 || $family_class == 7;
        $this->is_sans_serif_font = $family_class == 8;
        $this->is_ornamental_font = $family_class == 9;
        $this->is_script_font = $family_class == 10;
        $this->is_symbolic_font = $family_class == 12;
        /* Skip over the PANOSE number. The interesting values for us overlap
         * with the font family class defined above.
         */
        $this->skip_bytes(10);
        /* The Unicode range is made up of four 4-byte unsigned long integers
         * which are used as bitfields covering a 128-bit range. Each bit
         * represents a Unicode code block. If the bit is set, this font at
         * least partially covers the characters in that block.
         */
        $unicode_range1 = $this->read_u_int(4);
        $unicode_range2 = $this->read_u_int(4);
        $unicode_range3 = $this->read_u_int(4);
        $unicode_range4 = $this->read_u_int(4);
        $this->_debug_log('Unicode ranges: 0x%x 0x%x 0x%x 0x%x', $unicode_range1, $unicode_range2, $unicode_range3, $unicode_range4);
        /* The Unicode range is currently only used to decide if the character
         * set covered by the font is a subset of the Adobe Latin set, meaning
         * it only has the basic latin set. If it covers any other characters,
         * even any of the extended latin characters, it is considered symbolic
         * to PDF and must be described differently in the Font Descriptor.
         */
        /**
         * @todo Font is recognized as Adobe Latin subset font if it only contains
         * Basic Latin characters (only bit 0 of Unicode range bits is set).
         * Actually, other Unicode subranges like General Punctuation (bit 31) also
         * fall into Adobe Latin characters. So this code has to be modified.
         */
        $this->is_adobe_latin_subset = $unicode_range1 == 1 && $unicode_range2 == 0 && $unicode_range3 == 0 && $unicode_range4 == 0;
        $this->_debug_log(($this->is_adobe_latin_subset ? 'Is' : 'Is not') . ' a subset of Adobe Latin');
        $this->vendor_id = $this->read_bytes(4);
        /* Skip the font style bits. We use the values found in the 'head' table.
         * Also skip the first Unicode and last Unicode character indicies. Our
         * cmap implementation does not need these values.
         */
        $this->skip_bytes(6);
        /* Typographic ascender, descender, and line gap. These values are
         * preferred to those in the 'hhea' table.
         */
        $this->ascent = $this->read_int(2);
        $this->descent = $this->read_int(2);
        $this->line_gap = $this->read_int(2);
        /* The descent value is supposed to be negative--it's the distance
         * relative to the baseline. However, some fonts improperly store a
         * positive value in this field. If a positive value is found, flip the
         * sign and record a warning in the debug log that we did this.
         */
        if ($this->descent > 0) {
            $this->_debug_log('Warning: Font should specify negative descent. Actual: %d; Using %d', $this->descent, -$this->descent);
            $this->descent = -$this->descent;
        }
        /* Skip over Windows-specific ascent and descent.
         */
        $this->skip_bytes(4);
        /* Versions 0 and 1 tables do not contain the x or capital height
         * fields. Record zero for unknown.
         */
        if ($table_version < 2) {
            $this->x_height = 0;
            $this->capital_height = 0;
        } else {
            /* Skip over the Windows code page coverages. We are only concerned
             * with Unicode coverage.
             */
            $this->skip_bytes(8);
            $this->x_height = $this->read_int(2);
            $this->capital_height = $this->read_int(2);
            /* Ignore the remaining fields in this table. They are Windows-specific.
             */
        }
        /**
         * @todo Obtain the x and capital heights from the 'glyf' table if they
         *   haven't been supplied here instead of storing zero.
         */
    }
    /**
     * Parses the OpenType hmtx (Horizontal Metrics) table.
     *
     * The hmtx table contains the horizontal metrics for every glyph contained
     * within the font. These are the critical values for horizontal layout of
     * text.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_hmtx_table()
    {
        $this->_jump_to_table('hmtx');
        if (!$this->number_h_metrics) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('hhea table must be parsed prior to calling this method', Zend_Pdf_Exception::PARSED_OUT_OF_ORDER);
        }
        /* We only understand version 0 tables.
         */
        if ($this->metric_data_format != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unable to read format {$this->metric_data_format} table.", Zend_Pdf_Exception::DONT_UNDERSTAND_TABLE_VERSION);
        }
        /* The hmtx table has no header. For each glpyh in the font, it contains
         * the glyph's advance width and its left side bearing. We don't use the
         * left side bearing.
         */
        $glyph_widths = [];
        for ($i = 0; $i < $this->number_h_metrics; $i++) {
            $glyph_widths[$i] = $this->read_u_int(2);
            $this->skip_bytes(2);
        }
        /* Populate last value for the rest of array
         */
        while (count($glyph_widths) < $this->num_glyphs) {
            $glyph_widths[] = end($glyph_widths);
        }
        $this->glyph_widths = $glyph_widths;
        /* There is an optional table of left side bearings which is sometimes
         * used for monospaced fonts. We don't use the left side bearing, so
         * we can safely ignore it.
         */
    }
    /**
     * Parses the OpenType cmap (Character to Glyph Mapping) table.
     *
     * The cmap table provides the maps from character codes to font glyphs.
     * There are usually at least two character maps in a font: Microsoft Unicode
     * and Macintosh Roman. For very complex fonts, there may also be mappings
     * for the characters in the Unicode Surrogates Area, which are UCS-4
     * characters.
     *
     * @todo Need to rework the selection logic for picking a subtable. We should
     *   have an explicit list of preferences, followed by a list of those that
     *   are tolerable. Most specifically, since everything above this layer deals
     *   in Unicode, we need to be sure to only accept format 0 MacRoman tables.
     *
     * @throws Zend_Pdf_Exception
     */
    protected function _parse_cmap_table()
    {
        $this->_jump_to_table('cmap');
        $base_offset = $this->_table_directory['cmap']['offset'];
        /* We only understand version 0 tables.
         */
        $table_version = $this->read_u_int(2);
        if ($table_version != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unable to read version {$table_version} table", Zend_Pdf_Exception::DONT_UNDERSTAND_TABLE_VERSION);
        }
        $this->_debug_log('Version %d table', $table_version);
        $subtable_count = $this->read_u_int(2);
        $this->_debug_log('%d subtables', $subtable_count);
        /* Like the name table, there may be many different encoding subtables
         * present. Ideally, we are looking for an acceptable Unicode table.
         */
        $subtables = [];
        for ($subtable_index = 0; $subtable_index < $subtable_count; $subtable_index++) {
            $platform_id = $this->read_u_int(2);
            $encoding_id = $this->read_u_int(2);
            if (!($platform_id == 0 && $encoding_id == 3 || $platform_id == 0 && $encoding_id == 0 || $platform_id == 3 && $encoding_id == 1 || $platform_id == 1 && $encoding_id == 0)) {
                $this->_debug_log('Unsupported encoding: platformID: %d; encodingID: %d; skipping', $platform_id, $encoding_id);
                $this->skip_bytes(4);
                continue;
            }
            $subtable_offset = $this->read_u_int(4);
            if ($subtable_offset < 0) {
                // Sanity check for 4-byte unsigned on 32-bit platform
                $this->_debug_log('Offset 0x%x out of range for platformID: %d; skipping', $subtable_offset, $platform_id);
                continue;
            }
            $this->_debug_log('Found subtable; platformID: %d; encodingID: %d; offset: 0x%x (0x%x)', $platform_id, $encoding_id, $base_offset + $subtable_offset, $subtable_offset);
            $subtables[$platform_id][$encoding_id][] = $subtable_offset;
        }
        /* In preferred order, find a subtable to use.
         */
        $offsets = [];
        /* Unicode 2.0 or later semantics
         */
        if (isset($subtables[0][3])) {
            foreach ($subtables[0][3] as $offset) {
                $offsets[] = $offset;
            }
        }
        /* Unicode default semantics
         */
        if (isset($subtables[0][0])) {
            foreach ($subtables[0][0] as $offset) {
                $offsets[] = $offset;
            }
        }
        /* Microsoft Unicode
         */
        if (isset($subtables[3][1])) {
            foreach ($subtables[3][1] as $offset) {
                $offsets[] = $offset;
            }
        }
        /* Mac Roman.
         */
        if (isset($subtables[1][0])) {
            foreach ($subtables[1][0] as $offset) {
                $offsets[] = $offset;
            }
        }
        $cmap_type = -1;
        foreach ($offsets as $offset) {
            $cmap_offset = $base_offset + $offset;
            $this->move_to_offset($cmap_offset);
            $format = $this->read_u_int(2);
            $language = -1;
            switch ($format) {
                case 0x0:
                    $cmap_length = $this->read_u_int(2);
                    $language = $this->read_u_int(2);
                    if ($language != 0) {
                        $this->_debug_log('Type 0 cmap tables must be language-independent;' . ' language: %d; skipping', $language);
                    }
                    break;
                case 0x4:
                // break intentionally omitted
                case 0x6:
                    $cmap_length = $this->read_u_int(2);
                    $language = $this->read_u_int(2);
                    if ($language != 0) {
                        $this->_debug_log('Warning: cmap tables must be language-independent - this font' . ' may not work properly; language: %d', $language);
                    }
                    break;
                case 0x2:
                // break intentionally omitted
                case 0x8:
                // break intentionally omitted
                case 0xa:
                // break intentionally omitted
                case 0xc:
                    $this->_debug_log('Format: 0x%x currently unsupported; skipping', $format);
                    break;
                //$this->skipBytes(2);
                //$cmapLength = $this->readUInt(4);
                //$language = $this->readUInt(4);
                //if ($language != 0) {
                //    $this->_debugLog('Warning: cmap tables must be language-independent - this font'
                //                     . ' may not work properly; language: %d', $language);
                //}
                //break;
                default:
                    $this->_debug_log('Unknown subtable format: 0x%x; skipping', $format);
                    break;
            }
            $cmap_type = $format;
            break;
        }
        if ($cmap_type == -1) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Unable to find usable cmap table', Zend_Pdf_Exception::CANT_FIND_GOOD_CMAP);
        }
        /* Now extract the subtable data and create a Zend_Pdf_FontCmap object.
         */
        $this->_debug_log('Using cmap type %d; offset: 0x%x; length: %d', $cmap_type, $cmap_offset, $cmap_length);
        $this->move_to_offset($cmap_offset);
        $cmap_data = $this->read_bytes($cmap_length);
        #require_once 'Zend/Pdf/Cmap.php';
        $this->cmap = Zend_Pdf_Cmap::cmap_with_type_data($cmap_type, $cmap_data);
    }
    /**
     * Reads the scaler type from the header of the OpenType font file and
     * returns it as an unsigned long integer.
     *
     * The scaler type defines the type of font: OpenType font files may contain
     * TrueType or PostScript outlines. Throws an exception if the scaler type
     * is not recognized.
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    protected function _read_scaler_type()
    {
        if ($this->_scaler_type != 0) {
            return $this->_scaler_type;
        }
        $this->move_to_offset(0);
        $this->_scaler_type = $this->read_u_int(4);
        switch ($this->_scaler_type) {
            case 0x10000:
                // version 1.0 - Windows TrueType signature
                $this->_debug_log('Windows TrueType signature');
                break;
            case 0x74727565:
                // 'true' - Macintosh TrueType signature
                $this->_debug_log('Macintosh TrueType signature');
                break;
            case 0x4f54544f:
                // 'OTTO' - the CFF signature
                $this->_debug_log('PostScript CFF signature');
                break;
            case 0x74797031:
                // 'typ1'
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Unsupported font type: PostScript in sfnt wrapper', Zend_Pdf_Exception::WRONG_FONT_TYPE);
            default:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Not an OpenType font file', Zend_Pdf_Exception::WRONG_FONT_TYPE);
        }
        return $this->_scaler_type;
    }
    /**
     * Validates a given table's existence, then sets the file pointer to the
     * start of that table.
     *
     * @param string $tableName
     * @throws Zend_Pdf_Exception
     */
    protected function _jump_to_table($table_name)
    {
        if (empty($this->_table_directory[$table_name])) {
            // do not allow NULL or zero
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Required table '{$table_name}' not found!", Zend_Pdf_Exception::REQUIRED_TABLE_NOT_FOUND);
        }
        $this->_debug_log("Parsing {$table_name} table...");
        $this->move_to_offset($this->_table_directory[$table_name]['offset']);
    }
    /**
     * Reads the fixed 16.16 table version number and checks for compatibility.
     * If the version is incompatible, throws an exception. If it is compatible,
     * returns the version number.
     *
     * @param float $minVersion Minimum compatible version number.
     * @param float $maxVertion Maximum compatible version number.
     * @return float Table version number.
     * @throws Zend_Pdf_Exception
     */
    protected function _read_table_version($min_version, $max_version)
    {
        $table_version = $this->read_fixed(16, 16);
        if ($table_version < $min_version || $table_version > $max_version) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unable to read version {$table_version} table", Zend_Pdf_Exception::DONT_UNDERSTAND_TABLE_VERSION);
        }
        $this->_debug_log('Version %.2f table', $table_version);
        return $table_version;
    }
    /**
     * Utility method that returns ISO 639 two-letter language codes from the
     * TrueType platform and language ID. Returns NULL for languages that are
     * not supported.
     *
     * @param integer $platformID
     * @param integer $encodingID
     * @return string | null
     */
    protected function _language_code_for_platform($platform_id, $language_id)
    {
        if ($platform_id == 3) {
            // Microsoft encoding.
            /* The low-order bytes specify the language, the high-order bytes
             * specify the dialect. We just care about the language. For the
             * complete list, see:
             *   http://www.microsoft.com/globaldev/reference/lcid-all.mspx
             */
            $language_id &= 0xff;
            switch ($language_id) {
                case 0x9:
                    return 'en';
                case 0xc:
                    return 'fr';
                case 0x7:
                    return 'de';
                case 0x10:
                    return 'it';
                case 0x13:
                    return 'nl';
                case 0x1d:
                    return 'sv';
                case 0xa:
                    return 'es';
                case 0x6:
                    return 'da';
                case 0x16:
                    return 'pt';
                case 0x14:
                    return 'no';
                case 0xd:
                    return 'he';
                case 0x11:
                    return 'ja';
                case 0x1:
                    return 'ar';
                case 0xb:
                    return 'fi';
                case 0x8:
                    return 'el';
                default:
                    return null;
            }
        } elseif ($platform_id == 1) {
            // Macintosh encoding.
            switch ($language_id) {
                case 0:
                    return 'en';
                case 1:
                    return 'fr';
                case 2:
                    return 'de';
                case 3:
                    return 'it';
                case 4:
                    return 'nl';
                case 5:
                    return 'sv';
                case 6:
                    return 'es';
                case 7:
                    return 'da';
                case 8:
                    return 'pt';
                case 9:
                    return 'no';
                case 10:
                    return 'he';
                case 11:
                    return 'ja';
                case 12:
                    return 'ar';
                case 13:
                    return 'fi';
                case 14:
                    return 'el';
                default:
                    return null;
            }
        } else {
            // Unknown encoding.
            return null;
        }
    }
}