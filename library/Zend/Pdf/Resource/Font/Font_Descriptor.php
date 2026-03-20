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
/** Zend_Pdf_Font */
#require_once 'Zend/Pdf/Font.php';
/**
 * FontDescriptor implementation
 *
 * A font descriptor specifies metrics and other attributes of a simple font or a
 * CIDFont as a whole, as distinct from the metrics of individual glyphs. These font
 * metrics provide information that enables a viewer application to synthesize a
 * substitute font or select a similar font when the font program is unavailable. The
 * font descriptor may also be used to embed the font program in the PDF file.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_resource_font_font_Descriptor
{
    /**
     * Object constructor
     * @throws Zend_Pdf_Exception
     */
    public function __construct()
    {
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Zend_Pdf_Resource_Font_FontDescriptor is not intended to be instantiated');
    }
    /**
     * Object constructor
     *
     * The $embeddingOptions parameter allows you to set certain flags related
     * to font embedding. You may combine options by OR-ing them together. See
     * the EMBED_ constants defined in {@link Zend_Pdf_Font} for the list of
     * available options and their descriptions.
     *
     * Note that it is not requried that fonts be embedded within the PDF file
     * to use them. If the recipient of the PDF has the font installed on their
     * computer, they will see the correct fonts in the document. If they don't,
     * the PDF viewer will substitute or synthesize a replacement.
     *
     *
     * @param Zend_Pdf_Resource_Font $font Font
     * @param Zend_Pdf_FileParser_Font_OpenType $fontParser Font parser object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public static function factory(Zend_Pdf_Resource_Font $font, Zend_pdf_file_Parser_font_open_Type $font_parser, $embedding_options): \Zend_Pdf_Element_Dictionary
    {
        /* The font descriptor object contains the rest of the font metrics and
         * the information about the embedded font program (if applicible).
         */
        $font_descriptor = new Zend_Pdf_Element_Dictionary();
        $font_descriptor->Type = new Zend_Pdf_Element_Name('FontDescriptor');
        $font_descriptor->font_name = new Zend_Pdf_Element_Name($font->get_resource()->base_font->value);
        /* The font flags value is a bitfield that describes the stylistic
         * attributes of the font. We will set as many of the bits as can be
         * determined from the font parser.
         */
        $flags = 0;
        if ($font_parser->is_monospaced) {
            // bit 1: FixedPitch
            $flags |= 1 << 0;
        }
        if ($font_parser->is_serif_font) {
            // bit 2: Serif
            $flags |= 1 << 1;
        }
        if (!$font_parser->is_adobe_latin_subset) {
            // bit 3: Symbolic
            $flags |= 1 << 2;
        }
        if ($font_parser->is_script_font) {
            // bit 4: Script
            $flags |= 1 << 3;
        }
        if ($font_parser->is_adobe_latin_subset) {
            // bit 6: Nonsymbolic
            $flags |= 1 << 5;
        }
        if ($font_parser->is_italic) {
            // bit 7: Italic
            $flags |= 1 << 6;
        }
        // bits 17-19: AllCap, SmallCap, ForceBold; not available
        $font_descriptor->Flags = new Zend_Pdf_Element_Numeric($flags);
        $font_b_box = [new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->x_min)), new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->y_min)), new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->x_max)), new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->y_max))];
        $font_descriptor->font_b_box = new Zend_Pdf_Element_Array($font_b_box);
        $font_descriptor->italic_angle = new Zend_Pdf_Element_Numeric($font_parser->italic_angle);
        $font_descriptor->Ascent = new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->ascent));
        $font_descriptor->Descent = new Zend_Pdf_Element_Numeric($font->to_em_space($font_parser->descent));
        $font_descriptor->cap_height = new Zend_Pdf_Element_Numeric($font_parser->capital_height);
        /**
         * The vertical stem width is not yet extracted from the OpenType font
         * file. For now, record zero which is interpreted as 'unknown'.
         * @todo Calculate value for StemV.
         */
        $font_descriptor->stem_v = new Zend_Pdf_Element_Numeric(0);
        $font_descriptor->missing_width = new Zend_Pdf_Element_Numeric($font_parser->glyph_widths[0]);
        /* Set up font embedding. This is where the actual font program itself
         * is embedded within the PDF document.
         *
         * Note that it is not requried that fonts be embedded within the PDF
         * document to use them. If the recipient of the PDF has the font
         * installed on their computer, they will see the correct fonts in the
         * document. If they don't, the PDF viewer will substitute or synthesize
         * a replacement.
         *
         * There are several guidelines for font embedding:
         *
         * First, the developer might specifically request not to embed the font.
         */
        if (!($embedding_options & Zend_Pdf_Font::EMBED_DONT_EMBED)) {
            /* Second, the font author may have set copyright bits that prohibit
             * the font program from being embedded. Yes this is controversial,
             * but it's the rules:
             *   http://partners.adobe.com/public/developer/en/acrobat/sdk/FontPolicies.pdf
             *
             * To keep the developer in the loop, and to prevent surprising bug
             * reports of "your PDF doesn't have the right fonts," throw an
             * exception if the font cannot be embedded.
             */
            if (!$font_parser->is_embeddable) {
                /* This exception may be suppressed if the developer decides that
                 * it's not a big deal that the font program can't be embedded.
                 */
                if (!($embedding_options & Zend_Pdf_Font::EMBED_SUPPRESS_EMBED_EXCEPTION)) {
                    $message = 'This font cannot be embedded in the PDF document. If you would like to use ' . 'it anyway, you must pass Zend_Pdf_Font::EMBED_SUPPRESS_EMBED_EXCEPTION ' . 'in the $options parameter of the font constructor.';
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception($message, Zend_Pdf_Exception::FONT_CANT_BE_EMBEDDED);
                }
            } else {
                /* Otherwise, the default behavior is to embed all custom fonts.
                 */
                /* This section will change soon to a stream object data
                 * provider model so that we don't have to keep a copy of the
                 * entire font in memory.
                 *
                 * We also cannot build font subsetting until the data provider
                 * model is in place.
                 */
                $font_file = $font_parser->get_data_source()->read_all_bytes();
                $font_file_object = $font->get_factory()->new_stream_object($font_file);
                $font_file_object->dictionary->Length1 = new Zend_Pdf_Element_Numeric(strlen($font_file));
                if (!($embedding_options & Zend_Pdf_Font::EMBED_DONT_COMPRESS)) {
                    /* Compress the font file using Flate. This generally cuts file
                     * sizes by about half!
                     */
                    $font_file_object->dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
                }
                if ($font_parser instanceof Zend_pdf_file_Parser_font_open_Type_type1) {
                    $font_descriptor->font_file = $font_file_object;
                } elseif ($font_parser instanceof Zend_pdf_file_Parser_font_open_Type_true_Type) {
                    $font_descriptor->font_file2 = $font_file_object;
                } else {
                    $font_descriptor->font_file3 = $font_file_object;
                }
            }
        }
        return $font_descriptor;
    }
}