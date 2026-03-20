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
/** Internally used classes */
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Color */
#require_once 'Zend/Pdf/Color.php';
/**
 * GrayScale color implementation
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_color_gray_Scale extends Zend_Pdf_Color
{
    /**
     * GrayLevel.
     * 0.0 (black) - 1.0 (white)
     *
     * @var Zend_Pdf_Element_Numeric
     */
    private $_gray_level;
    /**
     * Object constructor
     *
     * @param float $grayLevel
     */
    public function __construct($gray_level)
    {
        if ($gray_level < 0) {
            $gray_level = 0;
        }
        if ($gray_level > 1) {
            $gray_level = 1;
        }
        $this->_gray_level = new Zend_Pdf_Element_Numeric($gray_level);
    }
    /**
     * Instructions, which can be directly inserted into content stream
     * to switch color.
     * Color set instructions differ for stroking and nonstroking operations.
     *
     * @param boolean $stroking
     */
    public function instructions($stroking): string
    {
        return $this->_gray_level->to_string() . ($stroking ? " G\n" : " g\n");
    }
    /**
     * Get color components (color space dependent)
     */
    public function get_components(): array
    {
        return [$this->_gray_level->value];
    }
}