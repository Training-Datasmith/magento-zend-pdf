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
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String.php';
/** Zend_Pdf_Outline */
#require_once 'Zend/Pdf/Outline.php';
/**
 * Traceable PDF outline representation class
 *
 * Instances of this class trace object update uperations. That allows to avoid outlines PDF tree update
 * which should be performed at each document update otherwise.
 *
 * @package    Zend_Pdf
 * @subpackage Outlines
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Outline_Loaded extends Zend_Pdf_Outline
{
    /**
     * Outline dictionary object
     *
     * @var Zend_Pdf_Element_Dictionary|Zend_Pdf_Element_Object|Zend_Pdf_Element_Reference
     */
    protected $_outline_dictionary;
    /**
     * original array of child outlines
     *
     * @var array
     */
    protected $_original_child_outlines = [];
    /**
     * Get outline title.
     *
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function get_title()
    {
        if ($this->_outline_dictionary->Title === null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outline dictionary Title entry is required.');
        }
        return $this->_outline_dictionary->Title->value;
    }
    /**
     * Set outline title
     *
     * @param string $title
     * @return Zend_Pdf_Outline
     */
    public function set_title($title): self
    {
        $this->_outline_dictionary->Title->touch();
        $this->_outline_dictionary->Title = new Zend_Pdf_Element_String($title);
        return $this;
    }
    /**
     * Sets 'isOpen' outline flag
     *
     * @param boolean $isOpen
     * @return Zend_Pdf_Outline
     */
    public function set_is_open($is_open)
    {
        parent::set_is_open($is_open);
        if ($this->_outline_dictionary->Count === null) {
            // Do Nothing.
            return this;
        }
        $children_count = $this->_outline_dictionary->Count->value;
        $is_open_current_state = $children_count > 0;
        if ($is_open != $is_open_current_state) {
            $this->_outline_dictionary->Count->touch();
            $this->_outline_dictionary->Count->value = ($is_open ? 1 : -1) * abs($children_count);
        }
        return $this;
    }
    /**
     * Returns true if outline item is displayed in italic
     *
     * @return boolean
     */
    public function is_italic()
    {
        if ($this->_outline_dictionary->F === null) {
            return false;
        }
        return $this->_outline_dictionary->F->value & 1;
    }
    /**
     * Sets 'isItalic' outline flag
     *
     * @param boolean $isItalic
     * @return Zend_Pdf_Outline
     */
    public function set_is_italic($is_italic): self
    {
        if ($this->_outline_dictionary->F === null) {
            $this->_outline_dictionary->touch();
            $this->_outline_dictionary->F = new Zend_Pdf_Element_Numeric($is_italic ? 1 : 0);
        } else {
            $this->_outline_dictionary->F->touch();
            if ($is_italic) {
                $this->_outline_dictionary->F->value = $this->_outline_dictionary->F->value | 1;
            } else {
                $this->_outline_dictionary->F->value = $this->_outline_dictionary->F->value | ~1;
            }
        }
        return $this;
    }
    /**
     * Returns true if outline item is displayed in bold
     *
     * @return boolean
     */
    public function is_bold()
    {
        if ($this->_outline_dictionary->F === null) {
            return false;
        }
        return $this->_outline_dictionary->F->value & 2;
    }
    /**
     * Sets 'isBold' outline flag
     *
     * @param boolean $isBold
     * @return Zend_Pdf_Outline
     */
    public function set_is_bold($is_bold): self
    {
        if ($this->_outline_dictionary->F === null) {
            $this->_outline_dictionary->touch();
            $this->_outline_dictionary->F = new Zend_Pdf_Element_Numeric($is_bold ? 2 : 0);
        } else {
            $this->_outline_dictionary->F->touch();
            if ($is_bold) {
                $this->_outline_dictionary->F->value = $this->_outline_dictionary->F->value | 2;
            } else {
                $this->_outline_dictionary->F->value = $this->_outline_dictionary->F->value | ~2;
            }
        }
        return $this;
    }
    /**
     * Get outline text color.
     *
     * @return Zend_Pdf_Color_Rgb
     */
    public function get_color()
    {
        if ($this->_outline_dictionary->C === null) {
            return null;
        }
        $components = $this->_outline_dictionary->C->items;
        #require_once 'Zend/Pdf/Color/Rgb.php';
        return new Zend_Pdf_Color_Rgb($components[0], $components[1], $components[2]);
    }
    /**
     * Set outline text color.
     * (null means default color which is black)
     *
     * @return Zend_Pdf_Outline
     */
    public function set_color(Zend_Pdf_Color_Rgb $color): self
    {
        $this->_outline_dictionary->touch();
        if ($color === null) {
            $this->_outline_dictionary->C = null;
        } else {
            $components = $color->get_components();
            $color_component_elements = [new Zend_Pdf_Element_Numeric($components[0]), new Zend_Pdf_Element_Numeric($components[1]), new Zend_Pdf_Element_Numeric($components[2])];
            $this->_outline_dictionary->C = new Zend_Pdf_Element_Array($color_component_elements);
        }
        return $this;
    }
    /**
     * Get outline target.
     *
     * @return Zend_Pdf_Target
     * @throws Zend_Pdf_Exception
     */
    public function get_target()
    {
        if ($this->_outline_dictionary->Dest !== null) {
            if ($this->_outline_dictionary->A !== null) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Outline dictionary may contain Dest or A entry, but not both.');
            }
            #require_once 'Zend/Pdf/Destination.php';
            return Zend_Pdf_Destination::load($this->_outline_dictionary->Dest);
        }
        if ($this->_outline_dictionary->A !== null) {
            #require_once 'Zend/Pdf/Action.php';
            return Zend_Pdf_Action::load($this->_outline_dictionary->A);
        }
        return null;
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
        $this->_outline_dictionary->touch();
        if (is_string($target)) {
            #require_once 'Zend/Pdf/Destination/Named.php';
            $target = Zend_Pdf_Destination_Named::create($target);
        }
        if ($target === null) {
            $this->_outline_dictionary->Dest = null;
            $this->_outline_dictionary->A = null;
        } elseif ($target instanceof Zend_Pdf_Destination) {
            $this->_outline_dictionary->Dest = $target->get_resource();
            $this->_outline_dictionary->A = null;
        } elseif ($target instanceof Zend_Pdf_Action) {
            $this->_outline_dictionary->Dest = null;
            $this->_outline_dictionary->A = $target->get_resource();
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outline target has to be Zend_Pdf_Destination or Zend_Pdf_Action object or string');
        }
        return $this;
    }
    /**
     * Set outline options
     *
     * @return Zend_Pdf_Actions_Traceable
     * @throws Zend_Pdf_Exception
     */
    public function set_options(array $options): self
    {
        parent::set_options($options);
        return $this;
    }
    /**
     * Create PDF outline object using specified dictionary
     *
     * @internal
     * @param Zend_Pdf_Element $dictionary (It's actually Dictionary or Dictionary Object or Reference to a Dictionary Object)
     * @param Zend_Pdf_Action  $parentAction
     * @param SplObjectStorage $processedOutlines  List of already processed Outline dictionaries,
     *                                             used to avoid cyclic references
     * @return Zend_Pdf_Action
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_Element $dictionary, ?Spl_Object_Storage $processed_dictionaries = null)
    {
        if ($dictionary->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('$dictionary mast be an indirect dictionary object.');
        }
        if ($processed_dictionaries === null) {
            $processed_dictionaries = new Spl_Object_Storage();
        }
        $processed_dictionaries->attach($dictionary);
        $this->_outline_dictionary = $dictionary;
        if ($dictionary->Count !== null) {
            if ($dictionary->Count->get_type() != Zend_Pdf_Element::TYPE_NUMERIC) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Outline dictionary Count entry must be a numeric element.');
            }
            $child_outlines_count = $dictionary->Count->value;
            if ($child_outlines_count > 0) {
                $this->_open = true;
            }
            $child_outlines_count = abs($child_outlines_count);
            $child_dictionary = $dictionary->First;
            $children = new Spl_Object_Storage();
            while ($child_dictionary !== null) {
                // Check children structure for cyclic references
                if ($children->contains($child_dictionary)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Outline childs load error.');
                }
                if (!$processed_dictionaries->contains($child_dictionary)) {
                    $this->child_outlines[] = new Zend_Pdf_Outline_Loaded($child_dictionary, $processed_dictionaries);
                }
                $child_dictionary = $child_dictionary->Next;
            }
            $this->_original_child_outlines = $this->child_outlines;
        }
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
        if ($update_navigation) {
            $this->_outline_dictionary->touch();
            $this->_outline_dictionary->Parent = $parent;
            $this->_outline_dictionary->Prev = $prev;
            $this->_outline_dictionary->Next = null;
        }
        $update_child_navigation = false;
        if (count($this->_original_child_outlines) != count($this->child_outlines)) {
            // If original and current children arrays have different size then children list was updated
            $update_child_navigation = true;
        } elseif (!(array_keys($this->_original_child_outlines) === array_keys($this->child_outlines))) {
            // If original and current children arrays have different keys (with a glance to an order) then children list was updated
            $update_child_navigation = true;
        } else {
            foreach ($this->child_outlines as $key => $child_outline) {
                if ($this->_original_child_outlines[$key] !== $child_outline) {
                    $update_child_navigation = true;
                    break;
                }
            }
        }
        $last_child = null;
        if ($update_child_navigation) {
            $this->_outline_dictionary->touch();
            $this->_outline_dictionary->First = null;
            foreach ($this->child_outlines as $child_outline) {
                if ($processed_outlines->contains($child_outline)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Outlines cyclyc reference is detected.');
                }
                if ($last_child === null) {
                    // First pass. Update Outlines dictionary First entry using corresponding value
                    $last_child = $child_outline->dump_outline($factory, $update_child_navigation, $this->_outline_dictionary, null, $processed_outlines);
                    $this->_outline_dictionary->First = $last_child;
                } else {
                    // Update previous outline dictionary Next entry (Prev is updated within dumpOutline() method)
                    $child_outline_dictionary = $child_outline->dump_outline($factory, $update_child_navigation, $this->_outline_dictionary, $last_child, $processed_outlines);
                    $last_child->Next = $child_outline_dictionary;
                    $last_child = $child_outline_dictionary;
                }
            }
            $this->_outline_dictionary->Last = $last_child;
            if (count($this->child_outlines) != 0) {
                $this->_outline_dictionary->Count = new Zend_Pdf_Element_Numeric(($this->is_open() ? 1 : -1) * count($this->child_outlines));
            } else {
                $this->_outline_dictionary->Count = null;
            }
        } else {
            foreach ($this->child_outlines as $child_outline) {
                if ($processed_outlines->contains($child_outline)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Outlines cyclyc reference is detected.');
                }
                $last_child = $child_outline->dump_outline($factory, $update_child_navigation, $this->_outline_dictionary, $last_child, $processed_outlines);
            }
        }
        return $this->_outline_dictionary;
    }
    public function dump($level = 0)
    {
        printf(":%3d:%s:%s:%s%s  :\n", count($this->child_outlines), $this->is_italic() ? 'i' : ' ', $this->is_bold() ? 'b' : ' ', str_pad('', 4 * $level), $this->get_title());
        if ($this->is_open() || true) {
            foreach ($this->child_outlines as $child) {
                $child->dump($level + 1);
            }
        }
    }
}