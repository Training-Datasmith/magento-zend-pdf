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
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String.php';
/** Zend_Pdf_Outline */
#require_once 'Zend/Pdf/Outline.php';
/**
 * PDF outline representation class
 *
 * @todo Implement an ability to associate an outline item with a structure element (PDF 1.3 feature)
 *
 * @package    Zend_Pdf
 * @subpackage Outlines
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Outline_Created extends Zend_Pdf_Outline
{
    /**
     * Get outline title.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->_title;
    }
    /**
     * Set outline title
     *
     * @param string $title
     * @return Zend_Pdf_Outline
     */
    public function set_title($title): self
    {
        $this->_title = $title;
        return $this;
    }
    /**
     * Returns true if outline item is displayed in italic
     *
     * @return boolean
     */
    public function is_italic()
    {
        return $this->_italic;
    }
    /**
     * Sets 'isItalic' outline flag
     *
     * @param boolean $isItalic
     * @return Zend_Pdf_Outline
     */
    public function set_is_italic($is_italic): self
    {
        $this->_italic = $is_italic;
        return $this;
    }
    /**
     * Returns true if outline item is displayed in bold
     *
     * @return boolean
     */
    public function is_bold()
    {
        return $this->_bold;
    }
    /**
     * Sets 'isBold' outline flag
     *
     * @param boolean $isBold
     * @return Zend_Pdf_Outline
     */
    public function set_is_bold($is_bold): self
    {
        $this->_bold = $is_bold;
        return $this;
    }
    /**
     * Get outline text color.
     *
     * @return Zend_Pdf_Color_Rgb
     */
    public function get_color()
    {
        return $this->_color;
    }
    /**
     * Set outline text color.
     * (null means default color which is black)
     *
     * @return Zend_Pdf_Outline
     */
    public function set_color(Zend_Pdf_Color_Rgb $color): self
    {
        $this->_color = $color;
        return $this;
    }
    /**
     * Get outline target.
     *
     * @return Zend_Pdf_Target
     */
    public function get_target()
    {
        return $this->_target;
    }
    /**
     * Set outline target.
     * Null means no target
     *
     * @param Zend_Pdf_Target|string $target
     * @return Zend_Pdf_Outline
     * @throws Zend_Pdf_Exception
     */
    public function set_target($target = null): self
    {
        if (is_string($target)) {
            #require_once 'Zend/Pdf/Destination/Named.php';
            $target = new Zend_Pdf_Destination_Named($target);
        }
        if ($target === null || $target instanceof Zend_Pdf_Target) {
            $this->_target = $target;
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outline target has to be Zend_Pdf_Destination or Zend_Pdf_Action object or string');
        }
        return $this;
    }
    /**
     * Object constructor
     *
     * @throws Zend_Pdf_Exception
     */
    public function __construct(array $options = [])
    {
        if (!isset($options['title'])) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Title parameter is required.');
        }
        $this->set_options($options);
    }
    /**
     * Dump Outline and its child outlines into PDF structures
     *
     * Returns dictionary indirect object or reference
     *
     * @internal
     * @param Zend_Pdf_ElementFactory    $factory object factory for newly created indirect objects
     * @param boolean $updateNavigation  Update navigation flag
     * @param Zend_Pdf_Element $parent   Parent outline dictionary reference
     * @param Zend_Pdf_Element $prev     Previous outline dictionary reference
     * @param SplObjectStorage $processedOutlines  List of already processed outlines
     * @return Zend_Pdf_Element
     * @throws Zend_Pdf_Exception
     */
    public function dump_outline(Zend_pdf_element_Factory_interface $factory, $update_navigation, Zend_Pdf_Element $parent, ?Zend_Pdf_Element $prev = null, ?Spl_Object_Storage $processed_outlines = null)
    {
        if ($processed_outlines === null) {
            $processed_outlines = new Spl_Object_Storage();
        }
        $processed_outlines->attach($this);
        $outline_dictionary = $factory->new_object(new Zend_Pdf_Element_Dictionary());
        $outline_dictionary->Title = new Zend_Pdf_Element_String($this->get_title());
        $target = $this->get_target();
        if ($target === null) {
            // Do nothing
        } elseif ($target instanceof Zend_Pdf_Destination) {
            $outline_dictionary->Dest = $target->get_resource();
        } elseif ($target instanceof Zend_Pdf_Action) {
            $outline_dictionary->A = $target->get_resource();
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outline target has to be Zend_Pdf_Destination, Zend_Pdf_Action object or null');
        }
        $color = $this->get_color();
        if ($color !== null) {
            $components = $color->get_components();
            $color_component_elements = [new Zend_Pdf_Element_Numeric($components[0]), new Zend_Pdf_Element_Numeric($components[1]), new Zend_Pdf_Element_Numeric($components[2])];
            $outline_dictionary->C = new Zend_Pdf_Element_Array($color_component_elements);
        }
        if ($this->is_italic() || $this->is_bold()) {
            $outline_dictionary->F = new Zend_Pdf_Element_Numeric(($this->is_italic() ? 1 : 0) | ($this->is_bold() ? 2 : 0));
            // Bit 2 - Bold
        }
        $outline_dictionary->Parent = $parent;
        $outline_dictionary->Prev = $prev;
        $last_child = null;
        foreach ($this->child_outlines as $child_outline) {
            if ($processed_outlines->contains($child_outline)) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Outlines cyclyc reference is detected.');
            }
            if ($last_child === null) {
                $last_child = $child_outline->dump_outline($factory, true, $outline_dictionary, null, $processed_outlines);
                $outline_dictionary->First = $last_child;
            } else {
                $child_outline_dictionary = $child_outline->dump_outline($factory, true, $outline_dictionary, $last_child, $processed_outlines);
                $last_child->Next = $child_outline_dictionary;
                $last_child = $child_outline_dictionary;
            }
        }
        $outline_dictionary->Last = $last_child;
        if (count($this->child_outlines) != 0) {
            $outline_dictionary->Count = new Zend_Pdf_Element_Numeric(($this->is_open() ? 1 : -1) * count($this->child_outlines));
        }
        return $outline_dictionary;
    }
}