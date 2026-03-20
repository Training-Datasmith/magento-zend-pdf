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
 * @version    $Id: Style.php 20096 2010-01-06 02:05:09Z bkarwin $
 */
#require_once 'Zend/Pdf/Canvas/Interface.php';
/** Internally used classes */
#require_once 'Zend/Pdf/Element.php';
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/String/Binary.php';
#require_once 'Zend/Pdf/Element/Boolean.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Null.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String.php';
#require_once 'Zend/Pdf/Resource/GraphicsState.php';
#require_once 'Zend/Pdf/Resource/Font.php';
#require_once 'Zend/Pdf/Resource/Image.php';
/**
 * Canvas is an abstract rectangle drawing area which can be dropped into
 * page object at specified place.
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Canvas_Abstract implements Zend_Pdf_Canvas_Interface
{
    /**
     * Drawing instructions
     *
     * @var string
     */
    protected $_contents = '';
    /**
     * Current font
     *
     * @var Zend_Pdf_Resource_Font
     */
    protected $_font;
    /**
     * Current font size
     *
     * @var float
     */
    protected $_font_size;
    /**
     * Current style
     *
     * @var Zend_Pdf_Style
     */
    protected $_style;
    /**
     * Page dictionary (refers to an inderect Zend_Pdf_Element_Dictionary object).
     *
     * @var Zend_Pdf_Element_Reference|Zend_Pdf_Element_Object
     */
    protected $_dictionary;
    /**
     * Counter for the "Save" operations
     *
     * @var integer
     */
    protected $_save_count = 0;
    /**
     * Add procedureSet to the Page description
     *
     * @param string $procSetName
     */
    abstract protected function _add_proc_set($proc_set_name);
    /**
     * Attach resource to the canvas
     *
     * Method returns a name of the resource which can be used
     * as a resource reference within drawing instructions stream
     * Allowed types: 'ExtGState', 'ColorSpace', 'Pattern', 'Shading',
     * 'XObject', 'Font', 'Properties'
     *
     * @param string $type
     * @return string
     */
    abstract protected function _attach_resource($type, Zend_Pdf_Resource $resource);
    /**
     * Draw a canvas at the specified location
     *
     * If upper right corner is not specified then canvas heght and width
     * are used.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_canvas(Zend_Pdf_Canvas_Interface $canvas, $x1, $y1, $x2 = null, $y2 = null)
    {
        $this->save_gs();
        $this->translate($x1, $y1);
        if ($x2 === null) {
            $with = $canvas->get_width();
        } else {
            $with = $x2 - $x1;
        }
        if ($y2 === null) {
            $height = $canvas->get_height();
        } else {
            $height = $y2 - $y1;
        }
        $this->clip_rectangle(0, 0, $with, $height);
        if ($x2 !== null || $y2 !== null) {
            // Drawn canvas has to be scaled.
            if ($x2 !== null) {
                $x_scale = $with / $canvas->get_width();
            } else {
                $x_scale = 1;
            }
            if ($y2 !== null) {
                $y_scale = $height / $canvas->get_height();
            } else {
                $y_scale = 1;
            }
            $this->scale($x_scale, $y_scale);
        }
        $canvas->get_contents();
        /** @todo implementation */
        $this->restore_gs();
        return $this;
    }
    /**
     * Set fill color.
     *
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_fill_color(Zend_Pdf_Color $color)
    {
        $this->_add_proc_set('PDF');
        $this->_contents .= $color->instructions(false);
        return $this;
    }
    /**
     * Set line color.
     *
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_line_color(Zend_Pdf_Color $color)
    {
        $this->_add_proc_set('PDF');
        $this->_contents .= $color->instructions(true);
        return $this;
    }
    /**
     * Set line width.
     *
     * @param float $width
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_line_width($width)
    {
        $this->_add_proc_set('PDF');
        $width_obj = new Zend_Pdf_Element_Numeric($width);
        $this->_contents .= $width_obj->to_string() . " w\n";
        return $this;
    }
    /**
     * Set line dashing pattern
     *
     * Pattern is an array of floats: array(on_length, off_length, on_length, off_length, ...)
     * or Zend_Pdf_Page::LINE_DASHING_SOLID constant
     * Phase is shift from the beginning of line.
     *
     * @param mixed $pattern
     * @param array $phase
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_line_dashing_pattern($pattern, $phase = 0)
    {
        $this->_add_proc_set('PDF');
        #require_once 'Zend/Pdf/Page.php';
        if ($pattern === Zend_Pdf_Page::LINE_DASHING_SOLID) {
            $pattern = [];
            $phase = 0;
        }
        $dash_pattern = new Zend_Pdf_Element_Array();
        $phase_eleemnt = new Zend_Pdf_Element_Numeric($phase);
        foreach ($pattern as $dash_item) {
            $dash_element = new Zend_Pdf_Element_Numeric($dash_item);
            $dash_pattern->items[] = $dash_element;
        }
        $this->_contents .= $dash_pattern->to_string() . ' ' . $phase_eleemnt->to_string() . " d\n";
        return $this;
    }
    /**
     * Set current font.
     *
     * @param float $fontSize
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_font(Zend_Pdf_Resource_Font $font, $font_size)
    {
        $this->_add_proc_set('Text');
        $font_name = $this->_attach_resource('Font', $font);
        $this->_font = $font;
        $this->_font_size = $font_size;
        $font_name_obj = new Zend_Pdf_Element_Name($font_name);
        $font_size_obj = new Zend_Pdf_Element_Numeric($font_size);
        $this->_contents .= $font_name_obj->to_string() . ' ' . $font_size_obj->to_string() . " Tf\n";
        return $this;
    }
    /**
     * Set the style to use for future drawing operations on this page
     *
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_style(Zend_Pdf_Style $style)
    {
        $this->_add_proc_set('Text');
        $this->_add_proc_set('PDF');
        if ($style->get_font() !== null) {
            $this->set_font($style->get_font(), $style->get_font_size());
        }
        $this->_contents .= $style->instructions($this->_dictionary->Resources);
        $this->_style = $style;
        return $this;
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
     * Return the style, applied to the page.
     *
     * @return Zend_Pdf_Style
     */
    public function get_style()
    {
        return $this->_style;
    }
    /**
     * Save the graphics state of this page.
     * This takes a snapshot of the currently applied style, position, clipping area and
     * any rotation/translation/scaling that has been applied.
     *
     * @todo check for the open paths
     * @throws Zend_Pdf_Exception    - if a save is performed with an open path
     * @return Zend_Pdf_Canvas_Interface
     */
    public function save_gs()
    {
        $this->_save_count++;
        $this->_add_proc_set('PDF');
        $this->_contents .= " q\n";
        return $this;
    }
    /**
     * Restore the graphics state that was saved with the last call to saveGS().
     *
     * @throws Zend_Pdf_Exception   - if there is no previously saved state
     * @return Zend_Pdf_Canvas_Interface
     */
    public function restore_gs()
    {
        if ($this->_save_count-- <= 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Restoring graphics state which is not saved');
        }
        $this->_contents .= " Q\n";
        return $this;
    }
    /**
     * Set the transparancy
     *
     * $alpha == 0  - transparent
     * $alpha == 1  - opaque
     *
     * Transparency modes, supported by PDF:
     * Normal (default), Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn, HardLight,
     * SoftLight, Difference, Exclusion
     *
     * @param float $alpha
     * @param string $mode
     * @return Zend_Pdf_Canvas_Interface
     */
    public function set_alpha($alpha, $mode = 'Normal')
    {
        $this->_add_proc_set('Text');
        $this->_add_proc_set('PDF');
        $graphics_state = new Zend_pdf_resource_graphics_State();
        $graphics_state->set_alpha($alpha, $mode);
        $g_state_name = $this->_attach_resource('ExtGState', $graphics_state);
        $g_state_name_object = new Zend_Pdf_Element_Name($g_state_name);
        $this->_contents .= $g_state_name_object->to_string() . " gs\n";
        return $this;
    }
    /**
     * Intersect current clipping area with a circle.
     *
     * @param float $x
     * @param float $y
     * @param float $radius
     * @param float $startAngle
     * @param float $endAngle
     * @return Zend_Pdf_Canvas_Interface
     */
    public function clip_circle($x, $y, $radius, $start_angle = null, $end_angle = null)
    {
        $this->clip_ellipse($x - $radius, $y - $radius, $x + $radius, $y + $radius, $start_angle, $end_angle);
        return $this;
    }
    /**
     * Intersect current clipping area with a polygon.
     *
     * Method signatures:
     * drawEllipse($x1, $y1, $x2, $y2);
     * drawEllipse($x1, $y1, $x2, $y2, $startAngle, $endAngle);
     *
     * @todo process special cases with $x2-$x1 == 0 or $y2-$y1 == 0
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param float $startAngle
     * @param float $endAngle
     * @return Zend_Pdf_Canvas_Interface
     */
    public function clip_ellipse($x1, $y1, $x2, $y2, $start_angle = null, $end_angle = null)
    {
        $this->_add_proc_set('PDF');
        if ($x2 < $x1) {
            $temp = $x1;
            $x1 = $x2;
            $x2 = $temp;
        }
        if ($y2 < $y1) {
            $temp = $y1;
            $y1 = $y2;
            $y2 = $temp;
        }
        $x = ($x1 + $x2) / 2.0;
        $y = ($y1 + $y2) / 2.0;
        $x_c = new Zend_Pdf_Element_Numeric($x);
        $y_c = new Zend_Pdf_Element_Numeric($y);
        if ($start_angle !== null) {
            if ($start_angle != 0) {
                $start_angle = fmod($start_angle, M_PI * 2);
            }
            if ($end_angle != 0) {
                $end_angle = fmod($end_angle, M_PI * 2);
            }
            if ($start_angle > $end_angle) {
                $end_angle += M_PI * 2;
            }
            $clip_path = $x_c->to_string() . ' ' . $y_c->to_string() . " m\n";
            $clip_sectors = (int) ceil(($end_angle - $start_angle) / M_PI_4);
            $clip_radius = max($x2 - $x1, $y2 - $y1);
            for ($count = 0; $count <= $clip_sectors; $count++) {
                $p_angle = $start_angle + ($end_angle - $start_angle) * $count / (float) $clip_sectors;
                $p_x = new Zend_Pdf_Element_Numeric($x + cos($p_angle) * $clip_radius);
                $p_y = new Zend_Pdf_Element_Numeric($y + sin($p_angle) * $clip_radius);
                $clip_path .= $p_x->to_string() . ' ' . $p_y->to_string() . " l\n";
            }
            $this->_contents .= $clip_path . "h\nW\nn\n";
        }
        $x_left = new Zend_Pdf_Element_Numeric($x1);
        $x_right = new Zend_Pdf_Element_Numeric($x2);
        $y_up = new Zend_Pdf_Element_Numeric($y2);
        $y_down = new Zend_Pdf_Element_Numeric($y1);
        $x_delta = 2 * (M_SQRT2 - 1) * ($x2 - $x1) / 3.0;
        $y_delta = 2 * (M_SQRT2 - 1) * ($y2 - $y1) / 3.0;
        $xr = new Zend_Pdf_Element_Numeric($x + $x_delta);
        $xl = new Zend_Pdf_Element_Numeric($x - $x_delta);
        $yu = new Zend_Pdf_Element_Numeric($y + $y_delta);
        $yd = new Zend_Pdf_Element_Numeric($y - $y_delta);
        $this->_contents .= $x_c->to_string() . ' ' . $y_up->to_string() . " m\n" . $xr->to_string() . ' ' . $y_up->to_string() . ' ' . $x_right->to_string() . ' ' . $yu->to_string() . ' ' . $x_right->to_string() . ' ' . $y_c->to_string() . " c\n" . $x_right->to_string() . ' ' . $yd->to_string() . ' ' . $xr->to_string() . ' ' . $y_down->to_string() . ' ' . $x_c->to_string() . ' ' . $y_down->to_string() . " c\n" . $xl->to_string() . ' ' . $y_down->to_string() . ' ' . $x_left->to_string() . ' ' . $yd->to_string() . ' ' . $x_left->to_string() . ' ' . $y_c->to_string() . " c\n" . $x_left->to_string() . ' ' . $yu->to_string() . ' ' . $xl->to_string() . ' ' . $y_up->to_string() . ' ' . $x_c->to_string() . ' ' . $y_up->to_string() . " c\n" . "h\nW\nn\n";
        return $this;
    }
    /**
     * Intersect current clipping area with a polygon.
     *
     * @param array $x  - array of float (the X co-ordinates of the vertices)
     * @param array $y  - array of float (the Y co-ordinates of the vertices)
     * @param integer $fillMethod
     * @return Zend_Pdf_Canvas_Interface
     */
    public function clip_polygon($x, $y, $fill_method = Zend_Pdf_Page::FILL_METHOD_NON_ZERO_WINDING)
    {
        $this->_add_proc_set('PDF');
        $first_point = true;
        foreach ($x as $id => $x_val) {
            $x_obj = new Zend_Pdf_Element_Numeric($x_val);
            $y_obj = new Zend_Pdf_Element_Numeric($y[$id]);
            if ($first_point) {
                $path = $x_obj->to_string() . ' ' . $y_obj->to_string() . " m\n";
                $first_point = false;
            } else {
                $path .= $x_obj->to_string() . ' ' . $y_obj->to_string() . " l\n";
            }
        }
        $this->_contents .= $path;
        if ($fill_method == Zend_Pdf_Page::FILL_METHOD_NON_ZERO_WINDING) {
            $this->_contents .= " h\n W\nn\n";
        } else {
            // Even-Odd fill method.
            $this->_contents .= " h\n W*\nn\n";
        }
        return $this;
    }
    /**
     * Intersect current clipping area with a rectangle.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return Zend_Pdf_Canvas_Interface
     */
    public function clip_rectangle($x1, $y1, $x2, $y2)
    {
        $this->_add_proc_set('PDF');
        $x1Obj = new Zend_Pdf_Element_Numeric($x1);
        $y1Obj = new Zend_Pdf_Element_Numeric($y1);
        $width_obj = new Zend_Pdf_Element_Numeric($x2 - $x1);
        $height2Obj = new Zend_Pdf_Element_Numeric($y2 - $y1);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $width_obj->to_string() . ' ' . $height2Obj->to_string() . " re\n" . " W\nn\n";
        return $this;
    }
    // ------------------------------------------------------------------------------------------
    /**
     * Draw a circle centered on x, y with a radius of radius.
     *
     * Method signatures:
     * drawCircle($x, $y, $radius);
     * drawCircle($x, $y, $radius, $fillType);
     * drawCircle($x, $y, $radius, $startAngle, $endAngle);
     * drawCircle($x, $y, $radius, $startAngle, $endAngle, $fillType);
     *
     *
     * It's not a really circle, because PDF supports only cubic Bezier curves.
     * But _very_ good approximation.
     * It differs from a real circle on a maximum 0.00026 radiuses
     * (at PI/8, 3*PI/8, 5*PI/8, 7*PI/8, 9*PI/8, 11*PI/8, 13*PI/8 and 15*PI/8 angles).
     * At 0, PI/4, PI/2, 3*PI/4, PI, 5*PI/4, 3*PI/2 and 7*PI/4 it's exactly a tangent to a circle.
     *
     * @param float $x
     * @param float $y
     * @param float $radius
     * @param mixed $param4
     * @param mixed $param5
     * @param mixed $param6
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_circle($x, $y, $radius, $param4 = null, $param5 = null, $param6 = null)
    {
        $this->draw_ellipse($x - $radius, $y - $radius, $x + $radius, $y + $radius, $param4, $param5, $param6);
        return $this;
    }
    /**
     * Draw an ellipse inside the specified rectangle.
     *
     * Method signatures:
     * drawEllipse($x1, $y1, $x2, $y2);
     * drawEllipse($x1, $y1, $x2, $y2, $fillType);
     * drawEllipse($x1, $y1, $x2, $y2, $startAngle, $endAngle);
     * drawEllipse($x1, $y1, $x2, $y2, $startAngle, $endAngle, $fillType);
     *
     * @todo process special cases with $x2-$x1 == 0 or $y2-$y1 == 0
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param mixed $param5
     * @param mixed $param6
     * @param mixed $param7
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_ellipse($x1, $y1, $x2, $y2, $param5 = null, $param6 = null, $param7 = null)
    {
        if ($param5 === null) {
            // drawEllipse($x1, $y1, $x2, $y2);
            $start_angle = null;
            $fill_type = Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE;
        } elseif ($param6 === null) {
            // drawEllipse($x1, $y1, $x2, $y2, $fillType);
            $start_angle = null;
            $fill_type = $param5;
        } else {
            // drawEllipse($x1, $y1, $x2, $y2, $startAngle, $endAngle);
            // drawEllipse($x1, $y1, $x2, $y2, $startAngle, $endAngle, $fillType);
            $start_angle = $param5;
            $end_angle = $param6;
            if ($param7 === null) {
                $fill_type = Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE;
            } else {
                $fill_type = $param7;
            }
        }
        $this->_add_proc_set('PDF');
        if ($x2 < $x1) {
            $temp = $x1;
            $x1 = $x2;
            $x2 = $temp;
        }
        if ($y2 < $y1) {
            $temp = $y1;
            $y1 = $y2;
            $y2 = $temp;
        }
        $x = ($x1 + $x2) / 2.0;
        $y = ($y1 + $y2) / 2.0;
        $x_c = new Zend_Pdf_Element_Numeric($x);
        $y_c = new Zend_Pdf_Element_Numeric($y);
        if ($start_angle !== null) {
            if ($start_angle != 0) {
                $start_angle = fmod($start_angle, M_PI * 2);
            }
            if ($end_angle != 0) {
                $end_angle = fmod($end_angle, M_PI * 2);
            }
            if ($start_angle > $end_angle) {
                $end_angle += M_PI * 2;
            }
            $clip_path = $x_c->to_string() . ' ' . $y_c->to_string() . " m\n";
            $clip_sectors = (int) ceil(($end_angle - $start_angle) / M_PI_4);
            $clip_radius = max($x2 - $x1, $y2 - $y1);
            for ($count = 0; $count <= $clip_sectors; $count++) {
                $p_angle = $start_angle + ($end_angle - $start_angle) * $count / (float) $clip_sectors;
                $p_x = new Zend_Pdf_Element_Numeric($x + cos($p_angle) * $clip_radius);
                $p_y = new Zend_Pdf_Element_Numeric($y + sin($p_angle) * $clip_radius);
                $clip_path .= $p_x->to_string() . ' ' . $p_y->to_string() . " l\n";
            }
            $this->_contents .= "q\n" . $clip_path . "h\nW\nn\n";
        }
        $x_left = new Zend_Pdf_Element_Numeric($x1);
        $x_right = new Zend_Pdf_Element_Numeric($x2);
        $y_up = new Zend_Pdf_Element_Numeric($y2);
        $y_down = new Zend_Pdf_Element_Numeric($y1);
        $x_delta = 2 * (M_SQRT2 - 1) * ($x2 - $x1) / 3.0;
        $y_delta = 2 * (M_SQRT2 - 1) * ($y2 - $y1) / 3.0;
        $xr = new Zend_Pdf_Element_Numeric($x + $x_delta);
        $xl = new Zend_Pdf_Element_Numeric($x - $x_delta);
        $yu = new Zend_Pdf_Element_Numeric($y + $y_delta);
        $yd = new Zend_Pdf_Element_Numeric($y - $y_delta);
        $this->_contents .= $x_c->to_string() . ' ' . $y_up->to_string() . " m\n" . $xr->to_string() . ' ' . $y_up->to_string() . ' ' . $x_right->to_string() . ' ' . $yu->to_string() . ' ' . $x_right->to_string() . ' ' . $y_c->to_string() . " c\n" . $x_right->to_string() . ' ' . $yd->to_string() . ' ' . $xr->to_string() . ' ' . $y_down->to_string() . ' ' . $x_c->to_string() . ' ' . $y_down->to_string() . " c\n" . $xl->to_string() . ' ' . $y_down->to_string() . ' ' . $x_left->to_string() . ' ' . $yd->to_string() . ' ' . $x_left->to_string() . ' ' . $y_c->to_string() . " c\n" . $x_left->to_string() . ' ' . $yu->to_string() . ' ' . $xl->to_string() . ' ' . $y_up->to_string() . ' ' . $x_c->to_string() . ' ' . $y_up->to_string() . " c\n";
        switch ($fill_type) {
            case Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE:
                $this->_contents .= " B*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_FILL:
                $this->_contents .= " f*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_STROKE:
                $this->_contents .= " S\n";
                break;
        }
        if ($start_angle !== null) {
            $this->_contents .= "Q\n";
        }
        return $this;
    }
    /**
     * Draw an image at the specified position on the page.
     *
     * @param Zend_Pdf_Image $image
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_image(Zend_Pdf_Resource_Image $image, $x1, $y1, $x2, $y2)
    {
        $this->_add_proc_set('PDF');
        $image_name = $this->_attach_resource('XObject', $image);
        $image_name_obj = new Zend_Pdf_Element_Name($image_name);
        $x1Obj = new Zend_Pdf_Element_Numeric($x1);
        $y1Obj = new Zend_Pdf_Element_Numeric($y1);
        $width_obj = new Zend_Pdf_Element_Numeric($x2 - $x1);
        $height_obj = new Zend_Pdf_Element_Numeric($y2 - $y1);
        $this->_contents .= "q\n" . '1 0 0 1 ' . $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " cm\n" . $width_obj->to_string() . ' 0 0 ' . $height_obj->to_string() . " 0 0 cm\n" . $image_name_obj->to_string() . " Do\n" . "Q\n";
        return $this;
    }
    /**
     * Draw a LayoutBox at the specified position on the page.
     *
     * @internal (not implemented now)
     *
     * @param Zend_Pdf_Element_LayoutBox $box
     * @param float $x
     * @param float $y
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_layout_box($box, $x, $y)
    {
        /** @todo implementation */
        return $this;
    }
    /**
     * Draw a line from x1,y1 to x2,y2.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_line($x1, $y1, $x2, $y2)
    {
        $this->_add_proc_set('PDF');
        $x1Obj = new Zend_Pdf_Element_Numeric($x1);
        $y1Obj = new Zend_Pdf_Element_Numeric($y1);
        $x2Obj = new Zend_Pdf_Element_Numeric($x2);
        $y2Obj = new Zend_Pdf_Element_Numeric($y2);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " m\n" . $x2Obj->to_string() . ' ' . $y2Obj->to_string() . " l\n S\n";
        return $this;
    }
    /**
     * Draw a polygon.
     *
     * If $fillType is Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE or
     * Zend_Pdf_Page::SHAPE_DRAW_FILL, then polygon is automatically closed.
     * See detailed description of these methods in a PDF documentation
     * (section 4.4.2 Path painting Operators, Filling)
     *
     * @param array $x  - array of float (the X co-ordinates of the vertices)
     * @param array $y  - array of float (the Y co-ordinates of the vertices)
     * @param integer $fillType
     * @param integer $fillMethod
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_polygon($x, $y, $fill_type = Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE, $fill_method = Zend_Pdf_Page::FILL_METHOD_NON_ZERO_WINDING)
    {
        $this->_add_proc_set('PDF');
        $first_point = true;
        foreach ($x as $id => $x_val) {
            $x_obj = new Zend_Pdf_Element_Numeric($x_val);
            $y_obj = new Zend_Pdf_Element_Numeric($y[$id]);
            if ($first_point) {
                $path = $x_obj->to_string() . ' ' . $y_obj->to_string() . " m\n";
                $first_point = false;
            } else {
                $path .= $x_obj->to_string() . ' ' . $y_obj->to_string() . " l\n";
            }
        }
        $this->_contents .= $path;
        switch ($fill_type) {
            case Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE:
                if ($fill_method == Zend_Pdf_Page::FILL_METHOD_NON_ZERO_WINDING) {
                    $this->_contents .= " b\n";
                } else {
                    // Even-Odd fill method.
                    $this->_contents .= " b*\n";
                }
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_FILL:
                if ($fill_method == Zend_Pdf_Page::FILL_METHOD_NON_ZERO_WINDING) {
                    $this->_contents .= " h\n f\n";
                } else {
                    // Even-Odd fill method.
                    $this->_contents .= " h\n f*\n";
                }
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_STROKE:
                $this->_contents .= " S\n";
                break;
        }
        return $this;
    }
    /**
     * Draw a rectangle.
     *
     * Fill types:
     * Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE - fill rectangle and stroke (default)
     * Zend_Pdf_Page::SHAPE_DRAW_STROKE      - stroke rectangle
     * Zend_Pdf_Page::SHAPE_DRAW_FILL        - fill rectangle
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param integer $fillType
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_rectangle($x1, $y1, $x2, $y2, $fill_type = Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE)
    {
        $this->_add_proc_set('PDF');
        $x1Obj = new Zend_Pdf_Element_Numeric($x1);
        $y1Obj = new Zend_Pdf_Element_Numeric($y1);
        $width_obj = new Zend_Pdf_Element_Numeric($x2 - $x1);
        $height2Obj = new Zend_Pdf_Element_Numeric($y2 - $y1);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $width_obj->to_string() . ' ' . $height2Obj->to_string() . " re\n";
        switch ($fill_type) {
            case Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE:
                $this->_contents .= " B*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_FILL:
                $this->_contents .= " f*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_STROKE:
                $this->_contents .= " S\n";
                break;
        }
        return $this;
    }
    /**
     * Draw a rounded rectangle.
     *
     * Fill types:
     * Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE - fill rectangle and stroke (default)
     * Zend_Pdf_Page::SHAPE_DRAW_STROKE      - stroke rectangle
     * Zend_Pdf_Page::SHAPE_DRAW_FILL        - fill rectangle
     *
     * radius is an integer representing radius of the four corners, or an array
     * of four integers representing the radius starting at top left, going
     * clockwise
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param integer|array $radius
     * @param integer $fillType
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_rounded_rectangle($x1, $y1, $x2, $y2, $radius, $fill_type = Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE)
    {
        $this->_add_proc_set('PDF');
        if (!is_array($radius)) {
            $radius = [$radius, $radius, $radius, $radius];
        } else {
            for ($i = 0; $i < 4; $i++) {
                if (!isset($radius[$i])) {
                    $radius[$i] = 0;
                }
            }
        }
        $top_left_x = $x1;
        $top_left_y = $y2;
        $top_right_x = $x2;
        $top_right_y = $y2;
        $bottom_right_x = $x2;
        $bottom_right_y = $y1;
        $bottom_left_x = $x1;
        $bottom_left_y = $y1;
        //draw top side
        $x1Obj = new Zend_Pdf_Element_Numeric($top_left_x + $radius[0]);
        $y1Obj = new Zend_Pdf_Element_Numeric($top_left_y);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " m\n";
        $x1Obj = new Zend_Pdf_Element_Numeric($top_right_x - $radius[1]);
        $y1Obj = new Zend_Pdf_Element_Numeric($top_right_y);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " l\n";
        //draw top right corner if needed
        if ($radius[1] != 0) {
            $x1Obj = new Zend_Pdf_Element_Numeric($top_right_x);
            $y1Obj = new Zend_Pdf_Element_Numeric($top_right_y);
            $x2Obj = new Zend_Pdf_Element_Numeric($top_right_x);
            $y2Obj = new Zend_Pdf_Element_Numeric($top_right_y);
            $x3Obj = new Zend_Pdf_Element_Numeric($top_right_x);
            $y3Obj = new Zend_Pdf_Element_Numeric($top_right_y - $radius[1]);
            $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $x2Obj->to_string() . ' ' . $y2Obj->to_string() . ' ' . $x3Obj->to_string() . ' ' . $y3Obj->to_string() . ' ' . " c\n";
        }
        //draw right side
        $x1Obj = new Zend_Pdf_Element_Numeric($bottom_right_x);
        $y1Obj = new Zend_Pdf_Element_Numeric($bottom_right_y + $radius[2]);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " l\n";
        //draw bottom right corner if needed
        if ($radius[2] != 0) {
            $x1Obj = new Zend_Pdf_Element_Numeric($bottom_right_x);
            $y1Obj = new Zend_Pdf_Element_Numeric($bottom_right_y);
            $x2Obj = new Zend_Pdf_Element_Numeric($bottom_right_x);
            $y2Obj = new Zend_Pdf_Element_Numeric($bottom_right_y);
            $x3Obj = new Zend_Pdf_Element_Numeric($bottom_right_x - $radius[2]);
            $y3Obj = new Zend_Pdf_Element_Numeric($bottom_right_y);
            $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $x2Obj->to_string() . ' ' . $y2Obj->to_string() . ' ' . $x3Obj->to_string() . ' ' . $y3Obj->to_string() . ' ' . " c\n";
        }
        //draw bottom side
        $x1Obj = new Zend_Pdf_Element_Numeric($bottom_left_x + $radius[3]);
        $y1Obj = new Zend_Pdf_Element_Numeric($bottom_left_y);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " l\n";
        //draw bottom left corner if needed
        if ($radius[3] != 0) {
            $x1Obj = new Zend_Pdf_Element_Numeric($bottom_left_x);
            $y1Obj = new Zend_Pdf_Element_Numeric($bottom_left_y);
            $x2Obj = new Zend_Pdf_Element_Numeric($bottom_left_x);
            $y2Obj = new Zend_Pdf_Element_Numeric($bottom_left_y);
            $x3Obj = new Zend_Pdf_Element_Numeric($bottom_left_x);
            $y3Obj = new Zend_Pdf_Element_Numeric($bottom_left_y + $radius[3]);
            $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $x2Obj->to_string() . ' ' . $y2Obj->to_string() . ' ' . $x3Obj->to_string() . ' ' . $y3Obj->to_string() . ' ' . " c\n";
        }
        //draw left side
        $x1Obj = new Zend_Pdf_Element_Numeric($top_left_x);
        $y1Obj = new Zend_Pdf_Element_Numeric($top_left_y - $radius[0]);
        $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . " l\n";
        //draw top left corner if needed
        if ($radius[0] != 0) {
            $x1Obj = new Zend_Pdf_Element_Numeric($top_left_x);
            $y1Obj = new Zend_Pdf_Element_Numeric($top_left_y);
            $x2Obj = new Zend_Pdf_Element_Numeric($top_left_x);
            $y2Obj = new Zend_Pdf_Element_Numeric($top_left_y);
            $x3Obj = new Zend_Pdf_Element_Numeric($top_left_x + $radius[0]);
            $y3Obj = new Zend_Pdf_Element_Numeric($top_left_y);
            $this->_contents .= $x1Obj->to_string() . ' ' . $y1Obj->to_string() . ' ' . $x2Obj->to_string() . ' ' . $y2Obj->to_string() . ' ' . $x3Obj->to_string() . ' ' . $y3Obj->to_string() . ' ' . " c\n";
        }
        switch ($fill_type) {
            case Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE:
                $this->_contents .= " B*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_FILL:
                $this->_contents .= " f*\n";
                break;
            case Zend_Pdf_Page::SHAPE_DRAW_STROKE:
                $this->_contents .= " S\n";
                break;
        }
        return $this;
    }
    /**
     * Draw a line of text at the specified position.
     *
     * @param string $text
     * @param float $x
     * @param float $y
     * @param string $charEncoding (optional) Character encoding of source text.
     *   Defaults to current locale.
     * @throws Zend_Pdf_Exception
     * @return Zend_Pdf_Canvas_Interface
     */
    public function draw_text($text, $x, $y, $char_encoding = '')
    {
        if ($this->_font === null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Font has not been set');
        }
        $this->_add_proc_set('Text');
        $text_obj = new Zend_Pdf_Element_String($this->_font->encode_string($text, $char_encoding));
        $x_obj = new Zend_Pdf_Element_Numeric($x);
        $y_obj = new Zend_Pdf_Element_Numeric($y);
        $this->_contents .= "BT\n" . $x_obj->to_string() . ' ' . $y_obj->to_string() . " Td\n" . $text_obj->to_string() . " Tj\n" . "ET\n";
        return $this;
    }
    /**
     * Close the path by drawing a straight line back to it's beginning.
     *
     * @internal (needs implementation)
     *
     * @throws Zend_Pdf_Exception    - if a path hasn't been started with pathMove()
     * @return Zend_Pdf_Canvas_Interface
     */
    public function path_close()
    {
        /** @todo implementation */
        return $this;
    }
    /**
     * Continue the open path in a straight line to the specified position.
     *
     * @internal (needs implementation)
     *
     * @param float $x  - the X co-ordinate to move to
     * @param float $y  - the Y co-ordinate to move to
     * @return Zend_Pdf_Canvas_Interface
     */
    public function path_line($x, $y)
    {
        /** @todo implementation */
        return $this;
    }
    /**
     * Start a new path at the specified position. If a path has already been started,
     * move the cursor without drawing a line.
     *
     * @internal (needs implementation)
     *
     * @param float $x  - the X co-ordinate to move to
     * @param float $y  - the Y co-ordinate to move to
     * @return Zend_Pdf_Canvas_Interface
     */
    public function path_move($x, $y)
    {
        /** @todo implementation */
        return $this;
    }
    /**
     * Rotate the page.
     *
     * @param float $x  - the X co-ordinate of rotation point
     * @param float $y  - the Y co-ordinate of rotation point
     * @param float $angle - rotation angle
     * @return Zend_Pdf_Canvas_Interface
     */
    public function rotate($x, $y, $angle)
    {
        $cos = new Zend_Pdf_Element_Numeric(cos($angle));
        $sin = new Zend_Pdf_Element_Numeric(sin($angle));
        $m_sin = new Zend_Pdf_Element_Numeric(-$sin->value);
        $x_obj = new Zend_Pdf_Element_Numeric($x);
        $y_obj = new Zend_Pdf_Element_Numeric($y);
        $m_x_obj = new Zend_Pdf_Element_Numeric(-$x);
        $m_y_obj = new Zend_Pdf_Element_Numeric(-$y);
        $this->_add_proc_set('PDF');
        $this->_contents .= '1 0 0 1 ' . $x_obj->to_string() . ' ' . $y_obj->to_string() . " cm\n" . $cos->to_string() . ' ' . $sin->to_string() . ' ' . $m_sin->to_string() . ' ' . $cos->to_string() . " 0 0 cm\n" . '1 0 0 1 ' . $m_x_obj->to_string() . ' ' . $m_y_obj->to_string() . " cm\n";
        return $this;
    }
    /**
     * Scale coordination system.
     *
     * @param float $xScale - X dimention scale factor
     * @param float $yScale - Y dimention scale factor
     * @return Zend_Pdf_Canvas_Interface
     */
    public function scale($x_scale, $y_scale)
    {
        $x_scale_obj = new Zend_Pdf_Element_Numeric($x_scale);
        $y_scale_obj = new Zend_Pdf_Element_Numeric($y_scale);
        $this->_add_proc_set('PDF');
        $this->_contents .= $x_scale_obj->to_string() . ' 0 0 ' . $y_scale_obj->to_string() . " 0 0 cm\n";
        return $this;
    }
    /**
     * Translate coordination system.
     *
     * @param float $xShift - X coordinate shift
     * @param float $yShift - Y coordinate shift
     * @return Zend_Pdf_Canvas_Interface
     */
    public function translate($x_shift, $y_shift)
    {
        $x_shift_obj = new Zend_Pdf_Element_Numeric($x_shift);
        $y_shift_obj = new Zend_Pdf_Element_Numeric($y_shift);
        $this->_add_proc_set('PDF');
        $this->_contents .= '1 0 0 1 ' . $x_shift_obj->to_string() . ' ' . $y_shift_obj->to_string() . " cm\n";
        return $this;
    }
    /**
     * Translate coordination system.
     *
     * @param float $x  - the X co-ordinate of axis skew point
     * @param float $y  - the Y co-ordinate of axis skew point
     * @param float $xAngle - X axis skew angle
     * @param float $yAngle - Y axis skew angle
     * @return Zend_Pdf_Canvas_Interface
     */
    public function skew($x, $y, $x_angle, $y_angle)
    {
        $tan_x_obj = new Zend_Pdf_Element_Numeric(tan($x_angle));
        $tan_y_obj = new Zend_Pdf_Element_Numeric(-tan($y_angle));
        $x_obj = new Zend_Pdf_Element_Numeric($x);
        $y_obj = new Zend_Pdf_Element_Numeric($y);
        $m_x_obj = new Zend_Pdf_Element_Numeric(-$x);
        $m_y_obj = new Zend_Pdf_Element_Numeric(-$y);
        $this->_add_proc_set('PDF');
        $this->_contents .= '1 0 0 1 ' . $x_obj->to_string() . ' ' . $y_obj->to_string() . " cm\n" . '1 ' . $tan_x_obj->to_string() . ' ' . $tan_y_obj->to_string() . " 1 0 0 cm\n" . '1 0 0 1 ' . $m_x_obj->to_string() . ' ' . $m_y_obj->to_string() . " cm\n";
        return $this;
    }
    /**
     * Writes the raw data to the page's content stream.
     *
     * Be sure to consult the PDF reference to ensure your syntax is correct. No
     * attempt is made to ensure the validity of the stream data.
     *
     * @param string $data
     * @param string $procSet (optional) Name of ProcSet to add.
     * @return Zend_Pdf_Canvas_Interface
     */
    public function raw_write($data, $proc_set = null)
    {
        if (!empty($proc_set)) {
            $this->_add_proc_set($proc_set);
        }
        $this->_contents .= $data;
        return $this;
    }
}