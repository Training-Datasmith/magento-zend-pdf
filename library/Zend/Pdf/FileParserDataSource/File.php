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
/** Zend_Pdf_FileParserDataSource */
#require_once 'Zend/Pdf/FileParserDataSource.php';
/**
 * Concrete subclass of {@link Zend_Pdf_FileParserDataSource} that provides an
 * interface to filesystem objects.
 *
 * Note that this class cannot be used for other sources that may be supported
 * by {@link fopen()} (through URL wrappers). It may be used for local
 * filesystem objects only.
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_file_Parser_Data_Source_file extends Zend_pdf_file_Parser_Data_Source
{
    /**** Instance Variables ****/
    /**
     * Fully-qualified path to the file.
     * @var string
     */
    protected $_file_path = '';
    /**
     * File resource handle .
     * @var resource
     */
    protected $_file_resource;
    /**** Public Interface ****/
    /* Concrete Class Implementation */
    /**
     * Object constructor.
     *
     * Validates the path to the file, ensures that it is readable, then opens
     * it for reading.
     *
     * Throws an exception if the file is missing or cannot be opened.
     *
     * @param string $filePath Fully-qualified path to the file.
     * @throws Zend_Pdf_Exception
     */
    public function __construct($file_path)
    {
        if (!(is_file($file_path) || is_link($file_path))) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Invalid file path: {$file_path}", Zend_Pdf_Exception::BAD_FILE_PATH);
        }
        if (!is_readable($file_path)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("File is not readable: {$file_path}", Zend_Pdf_Exception::NOT_READABLE);
        }
        if (($this->_size = @filesize($file_path)) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Error while obtaining file size: {$file_path}", Zend_Pdf_Exception::CANT_GET_FILE_SIZE);
        }
        if (($this->_file_resource = @fopen($file_path, 'rb')) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Cannot open file for reading: {$file_path}", Zend_Pdf_Exception::CANT_OPEN_FILE);
        }
        $this->_file_path = $file_path;
    }
    /**
     * Object destructor.
     *
     * Closes the file if it had been successfully opened.
     */
    public function __destruct()
    {
        if (is_resource($this->_file_resource)) {
            @fclose($this->_file_resource);
        }
    }
    /**
     * Returns the specified number of raw bytes from the file at the byte
     * offset of the current read position.
     *
     * Advances the read position by the number of bytes read.
     *
     * Throws an exception if an error was encountered while reading the file or
     * if there is insufficient data to completely fulfill the request.
     *
     * @param integer $byteCount Number of bytes to read.
     * @throws Zend_Pdf_Exception
     */
    public function read_bytes($byte_count): string
    {
        $bytes = @fread($this->_file_resource, $byte_count);
        if ($bytes === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Unexpected error while reading file', Zend_Pdf_Exception::ERROR_DURING_READ);
        }
        if (strlen($bytes) != $byte_count) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Insufficient data to read {$byte_count} bytes", Zend_Pdf_Exception::INSUFFICIENT_DATA);
        }
        $this->_offset += $byte_count;
        return $bytes;
    }
    /**
     * Returns the entire contents of the file as a string.
     *
     * Preserves the current file seek position.
     *
     * @return string
     */
    public function read_all_bytes()
    {
        return file_get_contents($this->_file_path);
    }
    /* Object Magic Methods */
    /**
     * Returns the full filesystem path of the file.
     */
    public function __toString(): string
    {
        return $this->_file_path;
    }
    /* Primitive Methods */
    /**
     * Seeks the file read position to the specified byte offset.
     *
     * Throws an exception if the file pointer cannot be moved or if it is
     * moved beyond EOF (end of file).
     *
     * @param integer $offset Destination byte offset.
     * @throws Zend_Pdf_Exception
     */
    public function move_to_offset($offset)
    {
        if ($this->_offset == $offset) {
            return;
            // Not moving; do nothing.
        }
        parent::move_to_offset($offset);
        $result = @fseek($this->_file_resource, $offset, SEEK_SET);
        if ($result !== 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Error while setting new file position', Zend_Pdf_Exception::CANT_SET_FILE_POSITION);
        }
        if (feof($this->_file_resource)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Moved beyond the end of the file', Zend_Pdf_Exception::MOVE_BEYOND_END_OF_FILE);
        }
    }
}