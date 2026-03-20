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
 * Style object.
 * Style object doesn't directly correspond to any PDF file object.
 * It's utility class, used as a container for style information.
 * It's used by Zend_Pdf_Page class in draw operations.
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Style
{
    /**
     * Fill color.
     * Used to fill geometric shapes or text.
     *
     * @var Zend_Pdf_Color|null
     */
    private $_fill_color;
    /**
     * Line color.
     * Current color, used for lines and font outlines.
     *
     * @var Zend_Pdf_Color|null
     */
    private $_color;
    /**
     * Line width.
     *
     * @var Zend_Pdf_Element_Numeric
     */
    private $_line_width;
    /**
     * Array which describes line dashing pattern.
     * It's array of numeric:
     * array($on_length, $off_length, $on_length, $off_length, ...)
     *
     * @var array
     */
    private $_line_dashing_pattern;
    /**
     * Line dashing phase
     *
     * @var float
     */
    private $_line_dashing_phase;
    /**
     * Current font
     *
     * @var Zend_Pdf_Resource_Font
     */
    private $_font;
    /**
     * Font size
     *
     * @var float
     */
    private $_font_size;
    /**
     * Create style.
     *
     * @param Zend_Pdf_Style $anotherStyle
     */
    public function __construct($another_style = null)
    {
        if ($another_style !== null) {
            $this->_fill_color = $another_style->_fill_color;
            $this->_color = $another_style->_color;
            $this->_line_width = $another_style->_line_width;
            $this->_line_dashing_pattern = $another_style->_line_dashing_pattern;
            $this->_line_dashing_phase = $another_style->_line_dashing_phase;
            $this->_font = $another_style->_font;
            $this->_font_size = $another_style->_font_size;
        }
    }
    /**
     * Set fill color.
     */
    public function set_fill_color(Zend_Pdf_Color $color)
    {
        $this->_fill_color = $color;
    }
    /**
     * Set line color.
     */
    public function set_line_color(Zend_Pdf_Color $color)
    {
        $this->_color = $color;
    }
    /**
     * Set line width.
     *
     * @param float $width
     */
    public function set_line_width($width)
    {
        #require_once 'Zend/Pdf/Element/Numeric.php';
        $this->_line_width = new Zend_Pdf_Element_Numeric($width);
    }
    /**
     * Set line dashing pattern
     *
     * @param array $pattern
     * @param float $phase
     */
    public function set_line_dashing_pattern($pattern, $phase = 0)
    {
        #require_once 'Zend/Pdf/Page.php';
        if ($pattern === Zend_Pdf_Page::LINE_DASHING_SOLID) {
            $pattern = [];
            $phase = 0;
        }
        #require_once 'Zend/Pdf/Element/Numeric.php';
        $this->_line_dashing_pattern = $pattern;
        $this->_line_dashing_phase = new Zend_Pdf_Element_Numeric($phase);
    }
    /**
     * Set current font.
     *
     * @param float $fontSize
     */
    public function set_font(Zend_Pdf_Resource_Font $font, $font_size)
    {
        $this->_font = $font;
        $this->_font_size = $font_size;
    }
    /**
     * Modify current font size
     *
     * @param float $fontSize
     */
    public function set_font_size($font_size)
    {
        $this->_font_size = $font_size;
    }
    /**
     * Get fill color.
     *
     * @return Zend_Pdf_Color|null
     */
    public function get_fill_color()
    {
        return $this->_fill_color;
    }
    /**
     * Get line color.
     *
     * @return Zend_Pdf_Color|null
     */
    public function get_line_color()
    {
        return $this->_color;
    }
    /**
     * Get line width.
     *
     * @return float
     */
    public function get_line_width()
    {
        return $this->_line_width->value;
    }
    /**
     * Get line dashing pattern
     *
     * @return array
     */
    public function get_line_dashing_pattern()
    {
        return $this->_line_dashing_pattern;
    }
    /**
     * Get current font.
     *
     * @return Zend_Pdf_Resource_Font $font
     */
    public function get_font()
    {
        return $this->_font;
    }
    /**
     * Get current font size
     *
     * @return float $fontSize
     */
    public function get_font_size()
    {
        return $this->_font_size;
    }
    /**
     * Get line dashing phase
     *
     * @return float
     */
    public function get_line_dashing_phase()
    {
        return $this->_line_dashing_phase->value;
    }
    /**
     * Dump style to a string, which can be directly inserted into content stream
     */
    public function instructions(): string
    {
        $instructions = '';
        if ($this->_fill_color !== null) {
            $instructions .= $this->_fill_color->instructions(false);
        }
        if ($this->_color !== null) {
            $instructions .= $this->_color->instructions(true);
        }
        if ($this->_line_width !== null) {
            $instructions .= $this->_line_width->to_string() . " w\n";
        }
        if ($this->_line_dashing_pattern !== null) {
            #require_once 'Zend/Pdf/Element/Array.php';
            $dash_pattern = new Zend_Pdf_Element_Array();
            #require_once 'Zend/Pdf/Element/Numeric.php';
            foreach ($this->_line_dashing_pattern as $dash_item) {
                $dash_element = new Zend_Pdf_Element_Numeric($dash_item);
                $dash_pattern->items[] = $dash_element;
            }
            $instructions .= $dash_pattern->to_string() . ' ' . $this->_line_dashing_phase->to_string() . " d\n";
        }
        return $instructions;
    }
}