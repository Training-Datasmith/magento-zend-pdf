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
 * PDF file trailer
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Trailer
{
    private static $_allowed_keys = ['Size', 'Prev', 'Root', 'Encrypt', 'Info', 'ID', 'Index', 'W', 'XRefStm', 'DocChecksum'];
    /**
     * Trailer dictionary.
     *
     * @var Zend_Pdf_Element_Dictionary
     */
    private $_dict;
    /**
     * Check if key is correct
     *
     * @param string $key
     * @throws Zend_Pdf_Exception
     */
    private function _check_dict_key($key)
    {
        if (!in_array($key, self::$_allowed_keys)) {
            /** @todo Make warning (log entry) instead of an exception */
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Unknown trailer dictionary key: '{$key}'.");
        }
    }
    /**
     * Object constructor
     */
    public function __construct(Zend_Pdf_Element_Dictionary $dict)
    {
        $this->_dict = $dict;
        foreach ($this->_dict->get_keys() as $dict_key) {
            $this->_check_dict_key($dict_key);
        }
    }
    /**
     * Get handler
     *
     * @return mixed
     */
    public function __get(string $property)
    {
        return $this->_dict->{$property};
    }
    /**
     * Set handler
     *
     * @param  mixed $value
     */
    public function __set(string $property, $value)
    {
        $this->_check_dict_key($property);
        $this->_dict->{$property} = $value;
    }
    /**
     * Return string trailer representation
     *
     * @return string
     */
    public function to_string()
    {
        return "trailer\n" . $this->_dict->to_string() . "\n";
    }
    /**
     * Get length of source PDF
     *
     * @return string
     */
    abstract public function get_pdf_length();
    /**
     * Get PDF String
     *
     * @return string
     */
    abstract public function get_pdf_string();
    /**
     * Get header of free objects list
     * Returns object number of last free object
     *
     * @return integer
     */
    abstract public function get_last_free_object();
}