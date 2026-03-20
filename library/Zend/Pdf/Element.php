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
 * PDF file element implementation
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Element
{
    public const TYPE_BOOL = 1;
    public const TYPE_NUMERIC = 2;
    public const TYPE_STRING = 3;
    public const TYPE_NAME = 4;
    public const TYPE_ARRAY = 5;
    public const TYPE_DICTIONARY = 6;
    public const TYPE_STREAM = 7;
    public const TYPE_NULL = 11;
    /**
     * Reference to the top level indirect object, which contains this element.
     *
     * @var Zend_Pdf_Element_Object
     */
    private $_parent_object;
    /**
     * Return type of the element.
     * See ZPdfPDFConst for possible values
     *
     * @return integer
     */
    abstract public function get_type();
    /**
     * Convert element to a string, which can be directly
     * written to a PDF file.
     *
     * $factory parameter defines operation context.
     *
     * @param Zend_Pdf_Factory $factory
     * @return string
     */
    abstract public function to_string($factory = null);
    public const CLONE_MODE_SKIP_PAGES = 1;
    // Do not follow pages during deep copy process
    public const CLONE_MODE_FORCE_CLONING = 2;
    // Force top level object cloning even it's already processed
    /**
     * Detach PDF object from the factory (if applicable), clone it and attach to new factory.
     *
     * @todo It's nevessry to check if SplObjectStorage class works faster
     * (Needs PHP 5.3.x to attach object _with_ additional data to storage)
     *
     * @param Zend_Pdf_ElementFactory $factory  The factory to attach
     * @param array &$processed List of already processed indirect objects, used to avoid objects duplication
     * @param integer $mode  Cloning mode (defines filter for objects cloning)
     * @returns Zend_Pdf_Element
     */
    public function make_clone(Zend_pdf_element_Factory $factory, array &$processed, $mode)
    {
        return clone $this;
    }
    /**
     * Set top level parent indirect object.
     */
    public function set_parent_object(Zend_Pdf_Element_Object $parent)
    {
        $this->_parent_object = $parent;
    }
    /**
     * Get top level parent indirect object.
     *
     * @return Zend_Pdf_Element_Object
     */
    public function get_parent_object()
    {
        return $this->_parent_object;
    }
    /**
     * Mark object as modified, to include it into new PDF file segment.
     *
     * We don't automate this action to keep control on PDF update process.
     * All new objects are treated as "modified" automatically.
     */
    public function touch()
    {
        if ($this->_parent_object !== null) {
            $this->_parent_object->touch();
        }
    }
    /**
     * Clean up resources, used by object
     */
    public function clean_up()
    {
        // Do nothing
    }
    /**
     * Convert PDF element to PHP type.
     *
     * @return mixed
     */
    abstract public function to_php();
    /**
     * Convert PHP value into PDF element.
     *
     * @param mixed $input
     * @return Zend_Pdf_Element
     */
    public static function php_to_pdf($input)
    {
        if (is_numeric($input)) {
            #require_once 'Zend/Pdf/Element/Numeric.php';
            return new Zend_Pdf_Element_Numeric($input);
        }
        if (is_bool($input)) {
            #require_once 'Zend/Pdf/Element/Boolean.php';
            return new Zend_Pdf_Element_Boolean($input);
        }
        if (is_array($input)) {
            $pdf_elements_array = [];
            $is_dictionary = false;
            foreach ($input as $key => $value) {
                if (is_string($key)) {
                    $is_dictionary = true;
                }
                $pdf_elements_array[$key] = Zend_Pdf_Element::php_to_pdf($value);
            }
            if ($is_dictionary) {
                #require_once 'Zend/Pdf/Element/Dictionary.php';
                return new Zend_Pdf_Element_Dictionary($pdf_elements_array);
            }
            #require_once 'Zend/Pdf/Element/Array.php';
            return new Zend_Pdf_Element_Array($pdf_elements_array);
        }
        #require_once 'Zend/Pdf/Element/String.php';
        return new Zend_Pdf_Element_String((string) $input);
    }
}