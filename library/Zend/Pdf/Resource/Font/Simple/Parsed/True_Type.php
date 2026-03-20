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
/** Zend_Pdf_Resource_Font_Simple_Parsed */
#require_once 'Zend/Pdf/Resource/Font/Simple/Parsed.php';
/**
 * TrueType fonts implementation
 *
 * Font objects should be normally be obtained from the factory methods
 * {@link Zend_Pdf_Font::fontWithName} and {@link Zend_Pdf_Font::fontWithPath}.
 *
 * @package    Zend_Pdf
 * @subpackage Fonts
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_resource_font_simple_parsed_true_Type extends Zend_Pdf_Resource_Font_Simple_Parsed
{
    /**
     * Object constructor
     *
     * @param Zend_Pdf_FileParser_Font_OpenType_TrueType $fontParser Font parser
     *   object containing parsed TrueType file.
     * @param integer $embeddingOptions Options for font embedding.
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_pdf_file_Parser_font_open_Type_true_Type $font_parser, $embedding_options)
    {
        parent::__construct($font_parser, $embedding_options);
        $this->_font_type = Zend_Pdf_Font::TYPE_TRUETYPE;
        $this->_resource->Subtype = new Zend_Pdf_Element_Name('TrueType');
        $font_descriptor = Zend_pdf_resource_font_font_Descriptor::factory($this, $font_parser, $embedding_options);
        $this->_resource->font_descriptor = $this->_object_factory->new_object($font_descriptor);
    }
}