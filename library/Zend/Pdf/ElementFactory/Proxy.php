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
/** Zend_Pdf_ElementFactory_Interface */
#require_once 'Zend/Pdf/ElementFactory/Interface.php';
/**
 * PDF element factory interface.
 * Responsibility is to log PDF changes
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_element_Factory_proxy implements Zend_pdf_element_Factory_interface
{
    /**
     * Factory object
     *
     * @var Zend_Pdf_ElementFactory_Interface
     */
    private $_factory;
    /**
     * Object constructor
     */
    public function __construct(Zend_pdf_element_Factory_interface $factory)
    {
        $this->_factory = $factory;
    }
    public function __destruct()
    {
        $this->_factory->close();
        $this->_factory = null;
    }
    /**
     * Get factory
     *
     * @return Zend_Pdf_ElementFactory_Interface
     */
    public function get_factory()
    {
        return $this->_factory->get_factory();
    }
    /**
     * Close factory and clean-up resources
     *
     * @internal
     */
    public function close()
    {
        // Do nothing
    }
    /**
     * Get source factory object
     *
     * @return Zend_Pdf_ElementFactory
     */
    public function resolve()
    {
        return $this->_factory->resolve();
    }
    /**
     * Get factory ID
     *
     * @return integer
     */
    public function get_id()
    {
        return $this->_factory->get_id();
    }
    /**
     * Set object counter
     *
     * @param integer $objCount
     */
    public function set_object_count($obj_count)
    {
        $this->_factory->set_object_count($obj_count);
    }
    /**
     * Get object counter
     *
     * @return integer
     */
    public function get_object_count()
    {
        return $this->_factory->get_object_count();
    }
    /**
     * Attach factory to the current;
     */
    public function attach(Zend_pdf_element_Factory_interface $factory)
    {
        $this->_factory->attach($factory);
    }
    /**
     * Calculate object enumeration shift.
     *
     * @internal
     * @return integer
     */
    public function calculate_shift(Zend_pdf_element_Factory_interface $factory)
    {
        return $this->_factory->calculate_shift($factory);
    }
    /**
     * Clean enumeration shift cache.
     * Has to be used after PDF render operation to let followed updates be correct.
     *
     * @param Zend_Pdf_ElementFactory_Interface $factory
     * @return integer
     */
    public function clean_enumeration_shift_cache()
    {
        return $this->_factory->clean_enumeration_shift_cache();
    }
    /**
     * Retrive object enumeration shift.
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function get_enumeration_shift(Zend_pdf_element_Factory_interface $factory)
    {
        return $this->_factory->get_enumeration_shift($factory);
    }
    /**
     * Mark object as modified in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function mark_as_modified(Zend_Pdf_Element_Object $obj)
    {
        $this->_factory->mark_as_modified($obj);
    }
    /**
     * Remove object in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function remove(Zend_Pdf_Element_Object $obj)
    {
        $this->_factory->remove($obj);
    }
    /**
     * Generate new Zend_Pdf_Element_Object
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @return Zend_Pdf_Element_Object
     */
    public function new_object(Zend_Pdf_Element $object_value)
    {
        return $this->_factory->new_object($object_value);
    }
    /**
     * Generate new Zend_Pdf_Element_Object_Stream
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @param mixed $objectValue
     * @return Zend_Pdf_Element_Object_Stream
     */
    public function new_stream_object($stream_value)
    {
        return $this->_factory->new_stream_object($stream_value);
    }
    /**
     * Enumerate modified objects.
     * Returns array of Zend_Pdf_UpdateInfoContainer
     *
     * @param Zend_Pdf_ElementFactory $rootFactory
     * @return array
     */
    public function list_modified_objects($root_factory = null)
    {
        return $this->_factory->list_modified_objects($root_factory);
    }
    /**
     * Check if PDF file was modified
     *
     * @return boolean
     */
    public function is_modified()
    {
        return $this->_factory->is_modified();
    }
}