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
 * @subpackage Actions
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Internally used classes */
#require_once 'Zend/Pdf/Element.php';
/**
 * PDF name tree representation class
 *
 * @todo implement lazy resource loading so resources will be really loaded at access time
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_name_Tree implements ArrayAccess, Iterator, Countable
{
    /**
     * Elements
     * Array of name => object tree entries
     *
     * @var array
     */
    protected $_items = [];
    /**
     * Object constructor
     *
     * @param Zend_Pdf_Element $rootDictionary root of name dictionary
     */
    public function __construct(Zend_Pdf_Element $root_dictionary)
    {
        if ($root_dictionary->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Name tree root must be a dictionary.');
        }
        $intermediate_nodes = [];
        $leaf_nodes = [];
        if ($root_dictionary->Kids !== null) {
            $intermediate_nodes[] = $root_dictionary;
        } else {
            $leaf_nodes[] = $root_dictionary;
        }
        while (count($intermediate_nodes) != 0) {
            $new_intermediate_nodes = [];
            foreach ($intermediate_nodes as $node) {
                foreach ($node->Kids->items as $child_node) {
                    if ($child_node->Kids !== null) {
                        $new_intermediate_nodes[] = $child_node;
                    } else {
                        $leaf_nodes[] = $child_node;
                    }
                }
            }
            $intermediate_nodes = $new_intermediate_nodes;
        }
        foreach ($leaf_nodes as $leaf_node) {
            $destinations_count = count($leaf_node->Names->items) / 2;
            for ($count = 0; $count < $destinations_count; $count++) {
                $this->_items[$leaf_node->Names->items[$count * 2]->value] = $leaf_node->Names->items[$count * 2 + 1];
            }
        }
    }
    #[\Return_Type_Will_Change]
    public function current()
    {
        return current($this->_items);
    }
    #[\Return_Type_Will_Change]
    public function next()
    {
        return next($this->_items);
    }
    #[\Return_Type_Will_Change]
    public function key()
    {
        return key($this->_items);
    }
    #[\Return_Type_Will_Change]
    public function valid()
    {
        return current($this->_items) !== false;
    }
    #[\Return_Type_Will_Change]
    public function rewind()
    {
        reset($this->_items);
    }
    #[\Return_Type_Will_Change]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_items);
    }
    #[\Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        return $this->_items[$offset];
    }
    #[\Return_Type_Will_Change]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }
    #[\Return_Type_Will_Change]
    public function offsetUnset($offset)
    {
        unset($this->_items[$offset]);
    }
    #[\Return_Type_Will_Change]
    public function clear()
    {
        $this->_items = [];
    }
    #[\Return_Type_Will_Change]
    public function count()
    {
        return count($this->_items);
    }
}