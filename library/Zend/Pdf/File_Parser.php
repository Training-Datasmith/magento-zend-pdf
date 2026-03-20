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
/**
 * Abstract utility class for parsing binary files.
 *
 * Provides a library of methods to quickly navigate and extract various data
 * types (signed and unsigned integers, floating- and fixed-point numbers,
 * strings, etc.) from the file.
 *
 * File access is managed via a {@link Zend_Pdf_FileParserDataSource} object.
 * This allows the same parser code to work with many different data sources:
 * in-memory objects, filesystem files, etc.
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_pdf_file_Parser
{
    /**** Class Constants ****/
    /**
     * Little-endian byte order (0x04 0x03 0x02 0x01).
     */
    public const BYTE_ORDER_LITTLE_ENDIAN = 0;
    /**
     * Big-endian byte order (0x01 0x02 0x03 0x04).
     */
    public const BYTE_ORDER_BIG_ENDIAN = 1;
    /**** Instance Variables ****/
    /**
     * Flag indicating that the file has passed a cursory validation check.
     * @var boolean
     */
    protected $_is_screened = false;
    /**
     * Flag indicating that the file has been sucessfully parsed.
     * @var boolean
     */
    protected $_is_parsed = false;
    /**
     * Object representing the data source to be parsed.
     * @var Zend_Pdf_FileParserDataSource
     */
    protected $_data_source;
    /**** Public Interface ****/
    /* Abstract Methods */
    /**
     * Performs a cursory check to verify that the binary file is in the expected
     * format. Intended to quickly weed out obviously bogus files.
     *
     * Must set $this->_isScreened to true if successful.
     *
     * @throws Zend_Pdf_Exception
     */
    abstract public function screen();
    /**
     * Reads and parses the complete binary file.
     *
     * Must set $this->_isParsed to true if successful.
     *
     * @throws Zend_Pdf_Exception
     */
    abstract public function parse();
    /* Object Lifecycle */
    /**
     * Object constructor.
     *
     * Verifies that the data source has been properly initialized.
     *
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_pdf_file_Parser_Data_Source $data_source)
    {
        if ($data_source->get_size() == 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('The data source has not been properly initialized', Zend_Pdf_Exception::BAD_DATA_SOURCE);
        }
        $this->_data_source = $data_source;
    }
    /**
     * Object destructor.
     *
     * Discards the data source object.
     */
    public function __destruct()
    {
        $this->_data_source = null;
    }
    /* Accessors */
    /**
     * Returns true if the file has passed a cursory validation check.
     *
     * @return boolean
     */
    public function is_screened()
    {
        return $this->_is_screened;
    }
    /**
     * Returns true if the file has been successfully parsed.
     *
     * @return boolean
     */
    public function is_parsed()
    {
        return $this->_is_parsed;
    }
    /**
     * Returns the data source object representing the file being parsed.
     *
     * @return Zend_Pdf_FileParserDataSource
     */
    public function get_data_source()
    {
        return $this->_data_source;
    }
    /* Primitive Methods */
    /**
     * Convenience wrapper for the data source object's moveToOffset() method.
     *
     * @param integer $offset Destination byte offset.
     * @throws Zend_Pdf_Exception
     */
    public function move_to_offset($offset)
    {
        $this->_data_source->move_to_offset($offset);
    }
    public function get_offset()
    {
        return $this->_data_source->get_offset();
    }
    public function get_size()
    {
        return $this->_data_source->get_size();
    }
    /**
     * Convenience wrapper for the data source object's readBytes() method.
     *
     * @param integer $byteCount Number of bytes to read.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_bytes($byte_count)
    {
        return $this->_data_source->read_bytes($byte_count);
    }
    /**
     * Convenience wrapper for the data source object's skipBytes() method.
     *
     * @param integer $byteCount Number of bytes to skip.
     * @throws Zend_Pdf_Exception
     */
    public function skip_bytes($byte_count)
    {
        $this->_data_source->skip_bytes($byte_count);
    }
    /* Parser Methods */
    /**
     * Reads the signed integer value from the binary file at the current byte
     * offset.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $size Size of integer in bytes: 1-4
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link Zend_Pdf_FileParser}.
     *   If omitted, uses big-endian.
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function read_int($size, $byte_order = Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN)
    {
        if ($size < 1 || $size > 4) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Invalid signed integer size: {$size}", Zend_Pdf_Exception::INVALID_INTEGER_SIZE);
        }
        $bytes = $this->_data_source->read_bytes($size);
        /* unpack() will not work for this method because it always works in
         * the host byte order for signed integers. It also does not allow for
         * variable integer sizes.
         */
        if ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN) {
            $number = ord($bytes[0]);
            if (($number & 0x80) == 0x80) {
                /* This number is negative. Extract the positive equivalent.
                 */
                $number = ~$number & 0xff;
                for ($i = 1; $i < $size; $i++) {
                    $number = $number << 8 | ~ord($bytes[$i]) & 0xff;
                }
                /* Now turn this back into a negative number by taking the
                 * two's complement (we didn't add one above so won't
                 * subtract it below). This works reliably on both 32- and
                 * 64-bit systems.
                 */
                $number = ~$number;
            } else {
                for ($i = 1; $i < $size; $i++) {
                    $number = $number << 8 | ord($bytes[$i]);
                }
            }
        } elseif ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_LITTLE_ENDIAN) {
            $number = ord($bytes[$size - 1]);
            if (($number & 0x80) == 0x80) {
                /* Negative number. See discussion above.
                 */
                $number = 0;
                for ($i = --$size; $i >= 0; $i--) {
                    $number |= (~ord($bytes[$i]) & 0xff) << $i * 8;
                }
                $number = ~$number;
            } else {
                $number = 0;
                for ($i = --$size; $i >= 0; $i--) {
                    $number |= ord($bytes[$i]) << $i * 8;
                }
            }
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Invalid byte order: {$byte_order}", Zend_Pdf_Exception::INVALID_BYTE_ORDER);
        }
        return $number;
    }
    /**
     * Reads the unsigned integer value from the binary file at the current byte
     * offset.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * NOTE: If you ask for a 4-byte unsigned integer on a 32-bit machine, the
     * resulting value WILL BE SIGNED because PHP uses signed integers internally
     * for everything. To guarantee portability, be sure to use bitwise operators
     * operators on large unsigned integers!
     *
     * @param integer $size Size of integer in bytes: 1-4
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link Zend_Pdf_FileParser}.
     *   If omitted, uses big-endian.
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function read_u_int($size, $byte_order = Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN)
    {
        if ($size < 1 || $size > 4) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Invalid unsigned integer size: {$size}", Zend_Pdf_Exception::INVALID_INTEGER_SIZE);
        }
        $bytes = $this->_data_source->read_bytes($size);
        /* unpack() is a bit heavyweight for this simple conversion. Just
         * work the bytes directly.
         */
        if ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN) {
            $number = ord($bytes[0]);
            for ($i = 1; $i < $size; $i++) {
                $number = $number << 8 | ord($bytes[$i]);
            }
        } elseif ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_LITTLE_ENDIAN) {
            $number = 0;
            for ($i = --$size; $i >= 0; $i--) {
                $number |= ord($bytes[$i]) << $i * 8;
            }
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Invalid byte order: {$byte_order}", Zend_Pdf_Exception::INVALID_BYTE_ORDER);
        }
        return $number;
    }
    /**
     * Returns true if the specified bit is set in the integer bitfield.
     *
     * @param integer $bit Bit number to test (i.e. - 0-31)
     * @param integer $bitField
     * @return boolean
     */
    public function is_bit_set($bit, $bit_field)
    {
        $bit_mask = 1 << $bit;
        return ($bit_field & $bit_mask) == $bit_mask;
    }
    /**
     * Reads the signed fixed-point number from the binary file at the current
     * byte offset.
     *
     * Common fixed-point sizes are 2.14 and 16.16.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $mantissaBits Number of bits in the mantissa
     * @param integer $fractionBits Number of bits in the fraction
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link Zend_Pdf_FileParser}.
     *   If omitted, uses big-endian.
     * @return float
     * @throws Zend_Pdf_Exception
     */
    public function read_fixed($mantissa_bits, $fraction_bits, $byte_order = Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN)
    {
        $bits_to_read = $mantissa_bits + $fraction_bits;
        if ($bits_to_read % 8 !== 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Fixed-point numbers are whole bytes', Zend_Pdf_Exception::BAD_FIXED_POINT_SIZE);
        }
        return $this->read_int($bits_to_read >> 3, $byte_order) / (1 << $fraction_bits);
    }
    /**
     * Reads the Unicode UTF-16-encoded string from the binary file at the
     * current byte offset.
     *
     * The byte order of the UTF-16 string must be specified. You must also
     * supply the desired resulting character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @todo Consider changing $byteCount to a character count. They are not
     *   always equivalent (in the case of surrogates).
     * @todo Make $byteOrder optional if there is a byte-order mark (BOM) in the
     *   string being extracted.
     *
     * @param integer $byteCount Number of bytes (characters * 2) to return.
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link Zend_Pdf_FileParser}.
     *   If omitted, uses big-endian.
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_utf16($byte_count, $byte_order = Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN, $character_set = '')
    {
        if ($byte_count == 0) {
            return '';
        }
        $bytes = $this->_data_source->read_bytes($byte_count);
        if ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN) {
            if ($character_set == 'UTF-16BE') {
                return $bytes;
            }
            return iconv('UTF-16BE', $character_set, $bytes);
        }
        if ($byte_order == Zend_pdf_file_Parser::BYTE_ORDER_LITTLE_ENDIAN) {
            if ($character_set == 'UTF-16LE') {
                return $bytes;
            }
            return iconv('UTF-16LE', $character_set, $bytes);
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception("Invalid byte order: {$byte_order}", Zend_Pdf_Exception::INVALID_BYTE_ORDER);
    }
    /**
     * Reads the Mac Roman-encoded string from the binary file at the current
     * byte offset.
     *
     * You must supply the desired resulting character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param integer $byteCount Number of bytes (characters) to return.
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_mac_roman($byte_count, $character_set = '')
    {
        if ($byte_count == 0) {
            return '';
        }
        $bytes = $this->_data_source->read_bytes($byte_count);
        if ($character_set == 'MacRoman') {
            return $bytes;
        }
        return iconv('MacRoman', $character_set, $bytes);
    }
    /**
     * Reads the Pascal string from the binary file at the current byte offset.
     *
     * The length of the Pascal string is determined by reading the length bytes
     * which preceed the character data. You must supply the desired resulting
     * character set.
     *
     * Advances the offset by the number of bytes read. Throws an exception if
     * an error occurs.
     *
     * @param string $characterSet (optional) Desired resulting character set.
     *   You may use any character set supported by {@link iconv()}. If omitted,
     *   uses 'current locale'.
     * @param integer $lengthBytes (optional) Number of bytes that make up the
     *   length. Default is 1.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_pascal($character_set = '', $length_bytes = 1)
    {
        $byte_count = $this->read_u_int($length_bytes);
        if ($byte_count == 0) {
            return '';
        }
        $bytes = $this->_data_source->read_bytes($byte_count);
        if ($character_set == 'ASCII') {
            return $bytes;
        }
        return iconv('ASCII', $character_set, $bytes);
    }
}