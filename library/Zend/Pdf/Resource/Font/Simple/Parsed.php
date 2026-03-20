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
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Resource_Font_Simple */
#require_once 'Zend/Pdf/Resource/Font/Simple.php';
/**
 * Parsed and (optionaly) embedded fonts implementation
 *
 * OpenType fonts can contain either TrueType or PostScript Type 1 outlines.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Resource_Font_Simple_Parsed extends Zend_Pdf_Resource_Font_Simple
{
    /**
     * Object constructor
     *
     * @param Zend_Pdf_FileParser_Font_OpenType $fontParser Font parser object containing OpenType file.
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
        $this->_glyph_widths = $font_parser->glyph_widths;
        $this->_missing_glyph_width = $this->_glyph_widths[0];
        $this->_cmap = $font_parser->cmap;
        /* Resource dictionary */
        $base_font = $this->get_font_name(Zend_Pdf_Font::NAME_POSTSCRIPT, 'en', 'UTF-8');
        $this->_resource->base_font = new Zend_Pdf_Element_Name($base_font);
        $this->_resource->first_char = new Zend_Pdf_Element_Numeric(0);
        $this->_resource->last_char = new Zend_Pdf_Element_Numeric(count($this->_glyph_widths) - 1);
        /* Now convert the scalar glyph widths to Zend_Pdf_Element_Numeric objects.
         */
        $pdf_widths = [];
        foreach ($this->_glyph_widths as $width) {
            $pdf_widths[] = new Zend_Pdf_Element_Numeric($this->to_em_space($width));
        }
        /* Create the Zend_Pdf_Element_Array object and add it to the font's
         * object factory and resource dictionary.
         */
        $widths_array_element = new Zend_Pdf_Element_Array($pdf_widths);
        $widths_object = $this->_object_factory->new_object($widths_array_element);
        $this->_resource->Widths = $widths_object;
    }
}