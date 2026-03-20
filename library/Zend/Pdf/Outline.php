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
/**
 * Abstract PDF outline representation class
 *
 * @todo Implement an ability to associate an outline item with a structure element (PDF 1.3 feature)
 *
 * @package    Zend_Pdf
 * @subpackage Outlines
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Outline implements Recursive_Iterator, Countable
{
    /**
     * True if outline is open.
     *
     * @var boolean
     */
    protected $_open = false;
    /**
     * Outline title.
     *
     * @var string
     */
    protected $_title;
    /**
     * True if outline item is displayed in italic.
     * Default value is false.
     *
     * @var boolean
     */
    protected $_italic = false;
    /**
     * Color to be used for the outline entry’s text.
     * It uses the DeviceRGB color space for color representation.
     * Null means default value - black ([0.0 0.0 0.0] in RGB representation).
     *
     * @var Zend_Pdf_Color_Rgb
     */
    protected $_color;
    /**
     * True if outline item is displayed in bold.
     * Default value is false.
     *
     * @var boolean
     */
    protected $_bold = false;
    /**
     * Target destination or action.
     * String means named destination
     *
     * Null means no target.
     *
     * @var Zend_Pdf_Destination|Zend_Pdf_Action
     */
    protected $_target;
    /**
     * Array of child outlines (array of Zend_Pdf_Outline objects)
     *
     * @var array
     */
    public $child_outlines = [];
    /**
     * Get outline title.
     *
     * @return string
     */
    abstract public function get_title();
    /**
     * Set outline title
     *
     * @param string $title
     * @return Zend_Pdf_Outline
     */
    abstract public function set_title($title);
    /**
     * Returns true if outline item is open by default
     *
     * @return boolean
     */
    public function is_open()
    {
        return $this->_open;
    }
    /**
     * Sets 'isOpen' outline flag
     *
     * @param boolean $isOpen
     * @return Zend_Pdf_Outline
     */
    public function set_is_open($is_open)
    {
        $this->_open = $is_open;
        return $this;
    }
    /**
     * Returns true if outline item is displayed in italic
     *
     * @return boolean
     */
    abstract public function is_italic();
    /**
     * Sets 'isItalic' outline flag
     *
     * @param boolean $isItalic
     * @return Zend_Pdf_Outline
     */
    abstract public function set_is_italic($is_italic);
    /**
     * Returns true if outline item is displayed in bold
     *
     * @return boolean
     */
    abstract public function is_bold();
    /**
     * Sets 'isBold' outline flag
     *
     * @param boolean $isBold
     * @return Zend_Pdf_Outline
     */
    abstract public function set_is_bold($is_bold);
    /**
     * Get outline text color.
     *
     * @return Zend_Pdf_Color_Rgb
     */
    abstract public function get_color();
    /**
     * Set outline text color.
     * (null means default color which is black)
     *
     * @return Zend_Pdf_Outline
     */
    abstract public function set_color(Zend_Pdf_Color_Rgb $color);
    /**
     * Get outline target.
     *
     * @return Zend_Pdf_Target
     */
    abstract public function get_target();
    /**
     * Set outline target.
     * Null means no target
     *
     * @param Zend_Pdf_Target|string $target
     * @return Zend_Pdf_Outline
     */
    abstract public function set_target($target = null);
    /**
     * Get outline options
     *
     * @return array
     */
    public function get_options()
    {
        return ['title' => $this->_title, 'open' => $this->_open, 'color' => $this->_color, 'italic' => $this->_italic, 'bold' => $this->_bold, 'target' => $this->_target];
    }
    /**
     * Set outline options
     *
     * @return Zend_Pdf_Action
     * @throws Zend_Pdf_Exception
     */
    public function set_options(array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'title':
                    $this->set_title($value);
                    break;
                case 'open':
                    $this->set_is_open($value);
                    break;
                case 'color':
                    $this->set_color($value);
                    break;
                case 'italic':
                    $this->set_is_italic($value);
                    break;
                case 'bold':
                    $this->set_is_bold($value);
                    break;
                case 'target':
                    $this->set_target($value);
                    break;
                default:
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception("Unknown option name - '{$key}'.");
            }
        }
        return $this;
    }
    /**
     * Create new Outline object
     *
     * It provides two forms of input parameters:
     *
     * 1. Zend_Pdf_Outline::create(string $title[, Zend_Pdf_Target $target])
     * 2. Zend_Pdf_Outline::create(array $options)
     *
     * Second form allows to provide outline options as an array.
     * The followed options are supported:
     *   'title'  - string, outline title, required
     *   'open'   - boolean, true if outline entry is open (default value is false)
     *   'color'  - Zend_Pdf_Color_Rgb object, true if outline entry is open (default value is null - black)
     *   'italic' - boolean, true if outline entry is displayed in italic (default value is false)
     *   'bold'   - boolean, true if outline entry is displayed in bold (default value is false)
     *   'target' - Zend_Pdf_Target object or string, outline item destination
     *
     * @return Zend_Pdf_Outline
     * @throws Zend_Pdf_Exception
     */
    public static function create($param1, $param2 = null)
    {
        #require_once 'Zend/Pdf/Outline/Created.php';
        if (is_string($param1)) {
            if ($param2 !== null && !($param2 instanceof Zend_Pdf_Target || is_string($param2))) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Outline create method takes $title (string) and $target (Zend_Pdf_Target or string) or an array as an input');
            }
            return new Zend_Pdf_Outline_Created(['title' => $param1, 'target' => $param2]);
        }
        if (!is_array($param1) || $param2 !== null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outline create method takes $title (string) and $destination (Zend_Pdf_Destination) or an array as an input');
        }
        return new Zend_Pdf_Outline_Created($param1);
    }
    /**
     * Returns number of the total number of open items at all levels of the outline.
     *
     * @internal
     * @return integer
     */
    public function open_outlines_count()
    {
        $count = 1;
        // Include this outline
        if ($this->is_open()) {
            foreach ($this->child_outlines as $child) {
                $count += $child->open_outlines_count();
            }
        }
        return $count;
    }
    /**
     * Dump Outline and its child outlines into PDF structures
     *
     * Returns dictionary indirect object or reference
     *
     * @param Zend_Pdf_ElementFactory    $factory object factory for newly created indirect objects
     * @param boolean $updateNavigation  Update navigation flag
     * @param Zend_Pdf_Element $parent   Parent outline dictionary reference
     * @param Zend_Pdf_Element $prev     Previous outline dictionary reference
     * @param SplObjectStorage $processedOutlines  List of already processed outlines
     * @return Zend_Pdf_Element
     */
    abstract public function dump_outline(Zend_pdf_element_Factory_interface $factory, $update_navigation, Zend_Pdf_Element $parent, ?Zend_Pdf_Element $prev = null, ?Spl_Object_Storage $processed_outlines = null);
    ////////////////////////////////////////////////////////////////////////
    //  RecursiveIterator interface methods
    //////////////
    /**
     * Returns the child outline.
     *
     * @return Zend_Pdf_Outline
     */
    #[\Return_Type_Will_Change]
    public function current()
    {
        return current($this->child_outlines);
    }
    /**
     * Returns current iterator key
     *
     * @return integer
     */
    #[\Return_Type_Will_Change]
    public function key()
    {
        return key($this->child_outlines);
    }
    /**
     * Go to next child
     */
    #[\Return_Type_Will_Change]
    public function next()
    {
        return next($this->child_outlines);
    }
    /**
     * Rewind children
     */
    #[\Return_Type_Will_Change]
    public function rewind()
    {
        return reset($this->child_outlines);
    }
    /**
     * Check if current position is valid
     *
     * @return boolean
     */
    #[\Return_Type_Will_Change]
    public function valid()
    {
        return current($this->child_outlines) !== false;
    }
    /**
     * Returns the child outline.
     *
     * @return Zend_Pdf_Outline|null
     */
    #[\Return_Type_Will_Change]
    public function get_children()
    {
        return current($this->child_outlines);
    }
    /**
     * Implements RecursiveIterator interface.
     *
     * @return bool  whether container has any pages
     */
    #[\Return_Type_Will_Change]
    public function has_children()
    {
        return count($this->child_outlines) > 0;
    }
    ////////////////////////////////////////////////////////////////////////
    //  Countable interface methods
    //////////////
    /**
     * count()
     *
     * @return int
     */
    #[\Return_Type_Will_Change]
    public function count()
    {
        return count($this->child_outlines);
    }
}