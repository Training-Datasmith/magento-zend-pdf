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
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/**
 * PDF reference object context
 * Reference context is defined by PDF parser and PDF Refernce table
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Element_Reference_Context
{
    /**
     * PDF parser object.
     *
     * @var Zend_Pdf_StringParser
     */
    private $_string_parser;
    /**
     * Reference table
     *
     * @var Zend_Pdf_Element_Reference_Table
     */
    private $_ref_table;
    /**
     * Object constructor
     */
    public function __construct(Zend_pdf_string_Parser $parser, Zend_Pdf_Element_Reference_Table $ref_table)
    {
        $this->_string_parser = $parser;
        $this->_ref_table = $ref_table;
    }
    /**
     * Context parser
     *
     * @return Zend_Pdf_StringParser
     */
    public function get_parser()
    {
        return $this->_string_parser;
    }
    /**
     * Context reference table
     *
     * @return Zend_Pdf_Element_Reference_Table
     */
    public function get_ref_table()
    {
        return $this->_ref_table;
    }
}