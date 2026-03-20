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
 * PDF file reference table
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Element_Reference_Table
{
    /**
     * Parent reference table
     *
     * @var Zend_Pdf_Element_Reference_Table
     */
    private $_parent;
    /**
     * Free entries
     * 'reference' => next free object number
     *
     * @var array
     */
    private $_free;
    /**
     * Generation numbers for free objects.
     * Array: objNum => nextGeneration
     *
     * @var array
     */
    private $_generations;
    /**
     * In use entries
     * 'reference' => offset
     *
     * @var array
     */
    private $_inuse;
    /**
     * Generation numbers for free objects.
     * Array: objNum => objGeneration
     *
     * @var array
     */
    private $_used_objects;
    /**
     * Object constructor
     */
    public function __construct()
    {
        $this->_parent = null;
        $this->_free = [];
        $this->_generations = [];
        $this->_inuse = [];
        $this->_used_objects = [];
    }
    /**
     * Add reference to the reference table
     *
     * @param string $ref
     * @param integer $offset
     * @param boolean $inuse
     */
    public function add_reference($ref, $offset, $inuse = true)
    {
        $ref_elements = explode(' ', $ref);
        if (!is_numeric($ref_elements[0]) || !is_numeric($ref_elements[1]) || $ref_elements[2] != 'R') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Incorrect reference: '{$ref}'");
        }
        $obj_num = (int) $ref_elements[0];
        $gen_num = (int) $ref_elements[1];
        if ($inuse) {
            $this->_inuse[$ref] = $offset;
            $this->_used_objects[$obj_num] = $obj_num;
        } else {
            $this->_free[$ref] = $offset;
            $this->_generations[$obj_num] = $gen_num;
        }
    }
    /**
     * Set parent reference table
     */
    public function set_parent(self $parent)
    {
        $this->_parent = $parent;
    }
    /**
     * Get object offset
     *
     * @param string $ref
     * @return integer
     */
    public function get_offset($ref)
    {
        if (isset($this->_inuse[$ref])) {
            return $this->_inuse[$ref];
        }
        if (isset($this->_free[$ref])) {
            return null;
        }
        if (isset($this->_parent)) {
            return $this->_parent->get_offset($ref);
        }
        return null;
    }
    /**
     * Get next object from a list of free objects.
     *
     * @param string $ref
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function get_next_free($ref)
    {
        if (isset($this->_inuse[$ref])) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Object is not free');
        }
        if (isset($this->_free[$ref])) {
            return $this->_free[$ref];
        }
        if (isset($this->_parent)) {
            return $this->_parent->get_next_free($ref);
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Object not found.');
    }
    /**
     * Get next generation number for free object
     *
     * @param integer $objNum
     * @return unknown
     */
    public function get_new_generation($obj_num)
    {
        if (isset($this->_used_objects[$obj_num])) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Object is not free');
        }
        if (isset($this->_generations[$obj_num])) {
            return $this->_generations[$obj_num];
        }
        if (isset($this->_parent)) {
            return $this->_parent->get_new_generation($obj_num);
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Object not found.');
    }
}