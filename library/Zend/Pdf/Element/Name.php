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
/** Zend_Pdf_Element */
#require_once 'Zend/Pdf/Element.php';
/**
 * PDF file 'name' element implementation
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Element_Name extends Zend_Pdf_Element
{
    /**
     * Object value
     *
     * @var string
     */
    public $value;
    /**
     * Object constructor
     *
     * @param string $val
     * @throws Zend_Pdf_Exception
     */
    public function __construct($val)
    {
        settype($val, 'string');
        if (strpos($val, "\x00") !== false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Null character is not allowed in PDF Names');
        }
        $this->value = $val;
    }
    /**
     * Return type of the element.
     */
    public function get_type(): int
    {
        return Zend_Pdf_Element::TYPE_NAME;
    }
    /**
     * Escape string according to the PDF rules
     *
     * @param string $inStr
     */
    public static function escape($in_str): string
    {
        $out_str = '';
        for ($count = 0; $count < strlen($in_str); $count++) {
            $next_code = ord($in_str[$count]);
            switch ($in_str[$count]) {
                case '(':
                // fall through to next case
                case ')':
                // fall through to next case
                case '<':
                // fall through to next case
                case '>':
                // fall through to next case
                case '[':
                // fall through to next case
                case ']':
                // fall through to next case
                case '{':
                // fall through to next case
                case '}':
                // fall through to next case
                case '/':
                // fall through to next case
                case '%':
                // fall through to next case
                case '\\':
                // fall through to next case
                case '#':
                    $out_str .= sprintf('#%02X', $next_code);
                    break;
                default:
                    if ($next_code >= 33 && $next_code <= 126) {
                        // Visible ASCII symbol
                        $out_str .= $in_str[$count];
                    } else {
                        $out_str .= sprintf('#%02X', $next_code);
                    }
            }
        }
        return $out_str;
    }
    /**
     * Unescape string according to the PDF rules
     *
     * @param string $inStr
     */
    public static function unescape($in_str): string
    {
        $out_str = '';
        for ($count = 0; $count < strlen($in_str); $count++) {
            if ($in_str[$count] != '#') {
                $out_str .= $in_str[$count];
            } else {
                // Escape sequence
                $out_str .= chr(base_convert(substr($in_str, $count + 1, 2), 16, 10));
                $count += 2;
            }
        }
        return $out_str;
    }
    /**
     * Return object as string
     *
     * @param Zend_Pdf_Factory $factory
     */
    public function to_string($factory = null): string
    {
        return '/' . self::escape((string) $this->value);
    }
    /**
     * Convert PDF element to PHP type.
     *
     * @return string
     */
    public function to_php()
    {
        return $this->value;
    }
}