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
 * PDF element factory interface.
 * Responsibility is to log PDF changes
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
interface Zend_pdf_element_Factory_interface
{
    /**
     * Get factory
     *
     * @return Zend_Pdf_ElementFactory_Interface
     */
    public function get_factory();
    /**
     * Close factory and clean-up resources
     *
     * @internal
     */
    public function close();
    /**
     * Get source factory object
     *
     * @return Zend_Pdf_ElementFactory
     */
    public function resolve();
    /**
     * Get factory ID
     *
     * @return integer
     */
    public function get_id();
    /**
     * Set object counter
     *
     * @param integer $objCount
     */
    public function set_object_count($obj_count);
    /**
     * Get object counter
     *
     * @return integer
     */
    public function get_object_count();
    /**
     * Attach factory to the current;
     */
    public function attach(Zend_pdf_element_Factory_interface $factory);
    /**
     * Calculate object enumeration shift.
     *
     * @return integer
     */
    public function calculate_shift(Zend_pdf_element_Factory_interface $factory);
    /**
     * Clean enumeration shift cache.
     * Has to be used after PDF render operation to let followed updates be correct.
     *
     * @param Zend_Pdf_ElementFactory_Interface $factory
     * @return integer
     */
    public function clean_enumeration_shift_cache();
    /**
     * Retrive object enumeration shift.
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function get_enumeration_shift(Zend_pdf_element_Factory_interface $factory);
    /**
     * Mark object as modified in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function mark_as_modified(Zend_Pdf_Element_Object $obj);
    /**
     * Remove object in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function remove(Zend_Pdf_Element_Object $obj);
    /**
     * Generate new Zend_Pdf_Element_Object
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @return Zend_Pdf_Element_Object
     */
    public function new_object(Zend_Pdf_Element $object_value);
    /**
     * Generate new Zend_Pdf_Element_Object_Stream
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @param mixed $objectValue
     * @return Zend_Pdf_Element_Object_Stream
     */
    public function new_stream_object($stream_value);
    /**
     * Enumerate modified objects.
     * Returns array of Zend_Pdf_UpdateInfoContainer
     *
     * @param Zend_Pdf_ElementFactory $rootFactory
     * @return array
     */
    public function list_modified_objects($root_factory = null);
    /**
     * Check if PDF file was modified
     *
     * @return boolean
     */
    public function is_modified();
}