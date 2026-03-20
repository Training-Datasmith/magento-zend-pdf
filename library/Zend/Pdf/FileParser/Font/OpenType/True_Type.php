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
/** Zend_Pdf_FileParser_Font_OpenType */
#require_once 'Zend/Pdf/FileParser/Font/OpenType.php';
/**
 * Parses an OpenType font file containing TrueType outlines.
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_file_Parser_font_open_Type_true_Type extends Zend_pdf_file_Parser_font_open_Type
{
    /**** Public Interface ****/
    /* Concrete Class Implementation */
    /**
     * Verifies that the font file actually contains TrueType outlines.
     *
     * @throws Zend_Pdf_Exception
     */
    public function screen()
    {
        if ($this->_is_screened) {
            return;
        }
        parent::screen();
        switch ($this->_read_scaler_type()) {
            case 0x10000:
                // version 1.0 - Windows TrueType signature
                break;
            case 0x74727565:
                // 'true' - Macintosh TrueType signature
                break;
            default:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Not a TrueType font file', Zend_Pdf_Exception::WRONG_FONT_TYPE);
        }
        $this->font_type = Zend_Pdf_Font::TYPE_TRUETYPE;
        $this->_is_screened = true;
    }
    /**
     * Reads and parses the TrueType font data from the file on disk.
     *
     * @throws Zend_Pdf_Exception
     */
    public function parse()
    {
        if ($this->_is_parsed) {
            return;
        }
        parent::parse();
        /* There is nothing additional to parse for TrueType fonts at this time.
         */
        $this->_is_parsed = true;
    }
}