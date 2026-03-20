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
 * Container which collects updated object info.
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_update_Info_Container
{
    /**
     * Object number
     *
     * @var integer
     */
    private $_obj_num;
    /**
     * Generation number
     *
     * @var integer
     */
    private $_gen_num;
    /**
     * Flag, which signals, that object is free
     *
     * @var boolean
     */
    private $_is_free;
    /**
     * String representation of the object
     *
     * @var Zend_Memory_Container|null
     */
    private $_dump;
    /**
     * Object constructor
     *
     * @param integer $objCount
     */
    public function __construct($obj_num, $gen_num, $is_free, $dump = null)
    {
        $this->_obj_num = $obj_num;
        $this->_gen_num = $gen_num;
        $this->_is_free = $is_free;
        if ($dump !== null) {
            if (strlen($dump) > 1024) {
                #require_once 'Zend/Pdf.php';
                $this->_dump = Zend_Pdf::get_memory_manager()->create($dump);
            } else {
                $this->_dump = $dump;
            }
        }
    }
    /**
     * Get object number
     *
     * @return integer
     */
    public function get_obj_num()
    {
        return $this->_obj_num;
    }
    /**
     * Get generation number
     *
     * @return integer
     */
    public function get_gen_num()
    {
        return $this->_gen_num;
    }
    /**
     * Check, that object is free
     *
     * @return boolean
     */
    public function is_free()
    {
        return $this->_is_free;
    }
    /**
     * Get string representation of the object
     *
     * @return string
     */
    public function get_object_dump()
    {
        if ($this->_dump === null) {
            return '';
        }
        if (is_string($this->_dump)) {
            return $this->_dump;
        }
        return $this->_dump->get_ref();
    }
}