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
#require_once 'Zend/Pdf/Element/Name.php';
/** Zend_Pdf_Resource_Font_FontDescriptor */
#require_once 'Zend/Pdf/Resource/Font/FontDescriptor.php';
/** Zend_Pdf_Resource_Font_CidFont */
#require_once 'Zend/Pdf/Resource/Font/CidFont.php';
/**
 * Type 2 CIDFonts implementation
 *
 * For Type 2, the CIDFont program is actually a TrueType font program, which has
 * no native notion of CIDs. In a TrueType font program, glyph descriptions are
 * identified by glyph index values. Glyph indices are internal to the font and are not
 * defined consistently from one font to another. Instead, a TrueType font program
 * contains a 'cmap' table that provides mappings directly from character codes to
 * glyph indices for one or more predefined encodings.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_resource_font_cid_Font_true_Type extends Zend_pdf_resource_font_cid_Font
{
    /**
     * Object constructor
     *
     * @todo Joing this class with Zend_Pdf_Resource_Font_Simple_Parsed_TrueType
     *
     * @param Zend_Pdf_FileParser_Font_OpenType_TrueType $fontParser Font parser
     *   object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_pdf_file_Parser_font_open_Type_true_Type $font_parser, $embedding_options)
    {
        parent::__construct($font_parser, $embedding_options);
        $this->_font_type = Zend_Pdf_Font::TYPE_CIDFONT_TYPE_2;
        $this->_resource->Subtype = new Zend_Pdf_Element_Name('CIDFontType2');
        $font_descriptor = Zend_pdf_resource_font_font_Descriptor::factory($this, $font_parser, $embedding_options);
        $this->_resource->font_descriptor = $this->_object_factory->new_object($font_descriptor);
        /* Prepare CIDToGIDMap */
        // Initialize 128K string of null characters (65536 2 byte integers)
        $cid_to_gid_map_data = str_repeat("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", 8192);
        // Fill the index
        $char_glyphs = $this->_cmap->get_covered_characters_glyphs();
        foreach ($char_glyphs as $char_code => $glyph) {
            $cid_to_gid_map_data[$char_code * 2] = chr($glyph >> 8);
            $cid_to_gid_map_data[$char_code * 2 + 1] = chr($glyph & 0xff);
        }
        // Store CIDToGIDMap within compressed stream object
        $cid_to_gid_map = $this->_object_factory->new_stream_object($cid_to_gid_map_data);
        $cid_to_gid_map->dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
        $this->_resource->cid_to_gid_map = $cid_to_gid_map;
    }
}