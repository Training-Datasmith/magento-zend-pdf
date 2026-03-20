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
 * @subpackage Destination
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Internally used classes */
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Null.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Destination_Explicit */
#require_once 'Zend/Pdf/Destination/Explicit.php';
/**
 * Zend_Pdf_Destination_Zoom explicit detination
 *
 * Destination array: [page /XYZ left top zoom]
 *
 * Display the page designated by page, with the coordinates (left, top) positioned
 * at the upper-left corner of the window and the contents of the page
 * magnified by the factor zoom. A null value for any of the parameters left, top,
 * or zoom specifies that the current value of that parameter is to be retained unchanged.
 * A zoom value of 0 has the same meaning as a null value.
 *
 * @package    Zend_Pdf
 * @subpackage Destination
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Destination_Zoom extends Zend_Pdf_Destination_Explicit
{
    /**
     * Create destination object
     *
     * @param Zend_Pdf_Page|integer $page  Page object or page number
     * @param float $left  Left edge of displayed page
     * @param float $top   Top edge of displayed page
     * @param float $zoom  Zoom factor
     * @throws Zend_Pdf_Exception
     */
    public static function create($page, $left = null, $top = null, $zoom = null): \Zend_Pdf_Destination_Zoom
    {
        $destination_array = new Zend_Pdf_Element_Array();
        if ($page instanceof Zend_Pdf_Page) {
            $destination_array->items[] = $page->get_page_dictionary();
        } elseif (is_integer($page)) {
            $destination_array->items[] = new Zend_Pdf_Element_Numeric($page);
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Page entry must be a Zend_Pdf_Page object or a page number.');
        }
        $destination_array->items[] = new Zend_Pdf_Element_Name('XYZ');
        if ($left === null) {
            $destination_array->items[] = new Zend_Pdf_Element_Null();
        } else {
            $destination_array->items[] = new Zend_Pdf_Element_Numeric($left);
        }
        if ($top === null) {
            $destination_array->items[] = new Zend_Pdf_Element_Null();
        } else {
            $destination_array->items[] = new Zend_Pdf_Element_Numeric($top);
        }
        if ($zoom === null) {
            $destination_array->items[] = new Zend_Pdf_Element_Null();
        } else {
            $destination_array->items[] = new Zend_Pdf_Element_Numeric($zoom);
        }
        return new Zend_Pdf_Destination_Zoom($destination_array);
    }
    /**
     * Get left edge of the displayed page (null means viewer application 'current value')
     *
     * @return float
     */
    public function get_left_edge()
    {
        return $this->_destination_array->items[2]->value;
    }
    /**
     * Set left edge of the displayed page (null means viewer application 'current value')
     *
     * @param float $left
     * @return Zend_Pdf_Action_Zoom
     */
    public function set_left_edge($left): self
    {
        if ($left === null) {
            $this->_destination_array->items[2] = new Zend_Pdf_Element_Null();
        } else {
            $this->_destination_array->items[2] = new Zend_Pdf_Element_Numeric($left);
        }
        return $this;
    }
    /**
     * Get top edge of the displayed page (null means viewer application 'current value')
     *
     * @return float
     */
    public function get_top_edge()
    {
        return $this->_destination_array->items[3]->value;
    }
    /**
     * Set top edge of the displayed page (null means viewer application 'current viewer')
     *
     * @param float $top
     * @return Zend_Pdf_Action_Zoom
     */
    public function set_top_edge($top): self
    {
        if ($top === null) {
            $this->_destination_array->items[3] = new Zend_Pdf_Element_Null();
        } else {
            $this->_destination_array->items[3] = new Zend_Pdf_Element_Numeric($top);
        }
        return $this;
    }
    /**
     * Get ZoomFactor of the displayed page (null or 0 means viewer application 'current value')
     *
     * @return float
     */
    public function get_zoom_factor()
    {
        return $this->_destination_array->items[4]->value;
    }
    /**
     * Set ZoomFactor of the displayed page (null or 0 means viewer application 'current viewer')
     *
     * @param float $zoom
     * @return Zend_Pdf_Action_Zoom
     */
    public function set_zoom_factor($zoom): self
    {
        if ($zoom === null) {
            $this->_destination_array->items[4] = new Zend_Pdf_Element_Null();
        } else {
            $this->_destination_array->items[4] = new Zend_Pdf_Element_Numeric($zoom);
        }
        return $this;
    }
}