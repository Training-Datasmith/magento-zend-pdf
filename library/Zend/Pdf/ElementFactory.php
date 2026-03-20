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
 * PDF element factory.
 * Responsibility is to log PDF changes
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_element_Factory implements Zend_pdf_element_Factory_interface
{
    /**
     * List of the modified objects.
     * Also contains new and removed objects
     *
     * Array: ojbectNumber => Zend_Pdf_Element_Object
     *
     * @var array
     */
    private $_modified_objects = [];
    /**
     * List of the removed objects
     *
     * Array: ojbectNumber => Zend_Pdf_Element_Object
     *
     * @var SplObjectStorage
     */
    private $_removed_objects;
    /**
     * List of registered objects.
     * Used for resources clean up when factory is destroyed.
     *
     * Array of Zend_Pdf_Element objects
     *
     * @var array
     */
    private $_registered_objects = [];
    /**
     * PDF object counter.
     * Actually it's an object number for new PDF object
     *
     * @var integer
     */
    private $_object_count;
    /**
     * List of the attached object factories.
     * Array of Zend_Pdf_ElementFactory_Interface objects
     *
     * @var array
     */
    private $_attached_factories = [];
    /**
     * Factory internal id
     *
     * @var integer
     */
    private $_factory_id;
    /**
     * Identity, used for factory id generation
     *
     * @var integer
     */
    private static $_identity = 0;
    /**
     * Internal cache to save calculated shifts
     *
     * @var array
     */
    private $_shift_calculation_cache = [];
    /**
     * Object constructor
     *
     * @param integer $objCount
     */
    public function __construct($obj_count)
    {
        $this->_object_count = (int) $obj_count;
        $this->_factory_id = self::$_identity++;
        $this->_removed_objects = new Spl_Object_Storage();
    }
    /**
     * Get factory
     *
     * @return Zend_Pdf_ElementFactory_Interface
     */
    public function get_factory(): self
    {
        return $this;
    }
    /**
     * Factory generator
     *
     * @param integer $objCount
     * @return Zend_Pdf_ElementFactory_Interface
     */
    public static function create_factory($obj_count): \Zend_pdf_element_Factory_proxy
    {
        #require_once 'Zend/Pdf/ElementFactory/Proxy.php';
        return new Zend_pdf_element_Factory_proxy(new Zend_pdf_element_Factory($obj_count));
    }
    /**
     * Close factory and clean-up resources
     *
     * @internal
     */
    public function close()
    {
        $this->_modified_objects = null;
        $this->_removed_objects = null;
        $this->_attached_factories = null;
        foreach ($this->_registered_objects as $obj) {
            $obj->clean_up();
        }
        $this->_registered_objects = null;
    }
    /**
     * Get source factory object
     */
    public function resolve(): self
    {
        return $this;
    }
    /**
     * Get factory ID
     *
     * @return integer
     */
    public function get_id()
    {
        return $this->_factory_id;
    }
    /**
     * Set object counter
     *
     * @param integer $objCount
     */
    public function set_object_count($obj_count)
    {
        $this->_object_count = (int) $obj_count;
    }
    /**
     * Get object counter
     *
     * @return integer
     */
    public function get_object_count()
    {
        $count = $this->_object_count;
        foreach ($this->_attached_factories as $attached) {
            $count += $attached->get_object_count() - 1;
            // -1 as "0" object is a special case and shared between factories
        }
        return $count;
    }
    /**
     * Attach factory to the current;
     */
    public function attach(Zend_pdf_element_Factory_interface $factory)
    {
        if ($factory === $this || isset($this->_attached_factories[$factory->get_id()])) {
            /**
             * Don't attach factory twice.
             * We do not check recusively because of nature of attach operation
             * (Pages are always attached to the Documents, Fonts are always attached
             * to the pages even if pages already use Document level object factory and so on)
             */
            return;
        }
        $this->_attached_factories[$factory->get_id()] = $factory;
    }
    /**
     * Calculate object enumeration shift.
     *
     * @return integer
     */
    public function calculate_shift(Zend_pdf_element_Factory_interface $factory)
    {
        if ($factory === $this) {
            return 0;
        }
        if (isset($this->_shift_calculation_cache[$factory->_factory_id])) {
            return $this->_shift_calculation_cache[$factory->_factory_id];
        }
        $shift = $this->_object_count - 1;
        foreach ($this->_attached_factories as $sub_factory) {
            $sub_factory_shift = $sub_factory->calculate_shift($factory);
            if ($sub_factory_shift != -1) {
                // context found
                $this->_shift_calculation_cache[$factory->_factory_id] = $shift + $sub_factory_shift;
                return $shift + $sub_factory_shift;
            }
            $shift += $sub_factory->get_object_count() - 1;
        }
        $this->_shift_calculation_cache[$factory->_factory_id] = -1;
        return -1;
    }
    /**
     * Clean enumeration shift cache.
     * Has to be used after PDF render operation to let followed updates be correct.
     */
    public function clean_enumeration_shift_cache()
    {
        $this->_shift_calculation_cache = [];
        foreach ($this->_attached_factories as $attached) {
            $attached->clean_enumeration_shift_cache();
        }
    }
    /**
     * Retrive object enumeration shift.
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    public function get_enumeration_shift(Zend_pdf_element_Factory_interface $factory)
    {
        if (($shift = $this->calculate_shift($factory)) == -1) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Wrong object context');
        }
        return $shift;
    }
    /**
     * Mark object as modified in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function mark_as_modified(Zend_Pdf_Element_Object $obj)
    {
        if ($obj->get_factory() !== $this) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Object is not generated by this factory');
        }
        $this->_modified_objects[$obj->get_obj_num()] = $obj;
    }
    /**
     * Remove object in context of current factory.
     *
     * @throws Zend_Pdf_Exception
     */
    public function remove(Zend_Pdf_Element_Object $obj)
    {
        if (!$obj->compare_factory($this)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Object is not generated by this factory');
        }
        $this->_modified_objects[$obj->get_obj_num()] = $obj;
        $this->_removed_objects->attach($obj);
    }
    /**
     * Generate new Zend_Pdf_Element_Object
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     */
    public function new_object(Zend_Pdf_Element $object_value): \Zend_Pdf_Element_Object
    {
        #require_once 'Zend/Pdf/Element/Object.php';
        $obj = new Zend_Pdf_Element_Object($object_value, $this->_object_count++, 0, $this);
        $this->_modified_objects[$obj->get_obj_num()] = $obj;
        return $obj;
    }
    /**
     * Generate new Zend_Pdf_Element_Object_Stream
     *
     * @todo Reusage of the freed object. It's not a support of new feature, but only improvement.
     *
     * @param mixed $objectValue
     */
    public function new_stream_object($stream_value): \Zend_Pdf_Element_Object_Stream
    {
        #require_once 'Zend/Pdf/Element/Object/Stream.php';
        $obj = new Zend_Pdf_Element_Object_Stream($stream_value, $this->_object_count++, 0, $this);
        $this->_modified_objects[$obj->get_obj_num()] = $obj;
        return $obj;
    }
    /**
     * Enumerate modified objects.
     * Returns array of Zend_Pdf_UpdateInfoContainer
     *
     * @param Zend_Pdf_ElementFactory_Interface $rootFactory
     */
    public function list_modified_objects($root_factory = null): array
    {
        if ($root_factory == null) {
            $root_factory = $this;
            $shift = 0;
        } else {
            $shift = $root_factory->get_enumeration_shift($this);
        }
        ksort($this->_modified_objects);
        $result = [];
        #require_once 'Zend/Pdf/UpdateInfoContainer.php';
        foreach ($this->_modified_objects as $obj_num => $obj) {
            if ($this->_removed_objects->offsetExists($obj)) {
                $result[$obj_num + $shift] = new Zend_pdf_update_Info_Container($obj_num + $shift, $obj->get_gen_num() + 1, true);
            } else {
                $result[$obj_num + $shift] = new Zend_pdf_update_Info_Container($obj_num + $shift, $obj->get_gen_num(), false, $obj->dump($root_factory));
            }
        }
        foreach ($this->_attached_factories as $factory) {
            $result += $factory->list_modified_objects($root_factory);
        }
        return $result;
    }
    /**
     * Register object in the factory
     *
     * It's used to clear "parent object" referencies when factory is closed and clean up resources
     *
     * @param string $refString
     */
    public function register_object(Zend_Pdf_Element_Object $obj, $ref_string)
    {
        $this->_registered_objects[$ref_string] = $obj;
    }
    /**
     * Fetch object specified by reference
     *
     * @param string $refString
     * @return Zend_Pdf_Element_Object|null
     */
    public function fetch_object($ref_string)
    {
        if (!isset($this->_registered_objects[$ref_string])) {
            return null;
        }
        return $this->_registered_objects[$ref_string];
    }
    /**
     * Check if PDF file was modified
     */
    public function is_modified(): bool
    {
        if (count($this->_modified_objects) != 0) {
            return true;
        }
        foreach ($this->_attached_factories as $sub_factory) {
            if ($sub_factory->is_modified()) {
                return true;
            }
        }
        return false;
    }
}