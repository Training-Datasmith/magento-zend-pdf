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
/** Internally used classes */
#require_once 'Zend/Pdf/Font.php';
/** Zend_Pdf_FileParser */
#require_once 'Zend/Pdf/FileParser.php';
/**
 * Abstract helper class for {@link Zend_Pdf_Font} that parses font files.
 *
 * Defines the public interface for concrete subclasses which are responsible
 * for parsing the raw binary data from the font file on disk. Also provides
 * a debug logging interface and a couple of shared utility methods.
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_pdf_file_Parser_font extends Zend_pdf_file_Parser
{
    /**** Instance Variables ****/
    /**
     * Array of parsed font properties. Used with {@link __get()} and
     * {@link __set()}.
     * @var array
     */
    private $_font_properties = [];
    /**
     * Flag indicating whether or not debug logging is active.
     * @var boolean
     */
    private $_debug = false;
    /**** Public Interface ****/
    /* Object Lifecycle */
    /**
     * Object constructor.
     *
     * Validates the data source and enables debug logging if so configured.
     *
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_pdf_file_Parser_Data_Source $data_source)
    {
        parent::__construct($data_source);
        $this->font_type = Zend_Pdf_Font::TYPE_UNKNOWN;
    }
    /* Accessors */
    /**
     * Get handler
     *
     * @return mixed
     */
    public function __get(string $property)
    {
        return $this->_font_properties[$property] ?? null;
    }
    /* NOTE: The set handler is defined below in the internal methods group. */
    /* Parser Methods */
    /**
     * Reads the Unicode UTF-16-encoded string from the binary file at the
     * current offset location. Overridden to fix return character set at UTF-16BE.
     *
     * @todo Deal with to-dos in the parent method.
     *
     * @param integer $byteCount Number of bytes (characters * 2) to return.
     * @param integer $byteOrder (optional) Big- or little-endian byte order.
     *   Use the BYTE_ORDER_ constants defined in {@link Zend_Pdf_FileParser}. If
     *   omitted, uses big-endian.
     * @param string $characterSet (optional) --Ignored--
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_utf16($byte_count, $byte_order = Zend_pdf_file_Parser::BYTE_ORDER_BIG_ENDIAN, $character_set = '')
    {
        return parent::read_string_utf16($byte_count, $byte_order, 'UTF-16BE');
    }
    /**
     * Reads the Mac Roman-encoded string from the binary file at the current
     * offset location. Overridden to fix return character set at UTF-16BE.
     *
     * @param integer $byteCount Number of bytes (characters) to return.
     * @param string $characterSet (optional) --Ignored--
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_mac_roman($byte_count, $character_set = '')
    {
        return parent::read_string_mac_roman($byte_count, 'UTF-16BE');
    }
    /**
     * Reads the Pascal string from the binary file at the current offset
     * location. Overridden to fix return character set at UTF-16BE.
     *
     * @param string $characterSet (optional) --Ignored--
     * @param integer $lengthBytes (optional) Number of bytes that make up the
     *   length. Default is 1.
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function read_string_pascal($character_set = '', $length_bytes = 1)
    {
        return parent::read_string_pascal('UTF-16BE');
    }
    /* Utility Methods */
    /**
     * Writes the entire font properties array to STDOUT. Used only for debugging.
     */
    public function write_debug()
    {
        print_r($this->_font_properties);
    }
    /**** Internal Methods ****/
    /* Internal Accessors */
    /**
     * Set handler
     *
     * NOTE: This method is protected. Other classes may freely interrogate
     * the font properties, but only this and its subclasses may set them.
     *
     * @param  mixed $value
     */
    public function __set(string $property, $value)
    {
        if ($value === null) {
            unset($this->_font_properties[$property]);
        } else {
            $this->_font_properties[$property] = $value;
        }
    }
    /* Internal Utility Methods */
    /**
     * If debug logging is enabled, writes the log message.
     *
     * The log message is a sprintf() style string and any number of arguments
     * may accompany it as additional parameters.
     *
     * @param string $message
     * @param mixed (optional, multiple) Additional arguments
     */
    protected function _debug_log($message)
    {
        if (!$this->_debug) {
            return;
        }
        if (func_num_args() > 1) {
            $args = func_get_args();
            $message = array_shift($args);
            $message = vsprintf($message, $args);
        }
        #require_once 'Zend/Log.php';
        $logger = new Zend_Log();
        $logger->log($message, Zend_Log::DEBUG);
    }
}