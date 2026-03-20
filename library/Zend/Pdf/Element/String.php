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
 * PDF file 'string' element implementation
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Element_String extends Zend_Pdf_Element
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
     */
    public function __construct($val)
    {
        $this->value = (string) $val;
    }
    /**
     * Return type of the element.
     */
    public function get_type(): int
    {
        return Zend_Pdf_Element::TYPE_STRING;
    }
    /**
     * Return object as string
     *
     * @param Zend_Pdf_Factory $factory
     */
    public function to_string($factory = null): string
    {
        return '(' . self::escape((string) $this->value) . ')';
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
    /**
     * Escape string according to the PDF rules
     *
     * @param string $str
     */
    public static function escape($str): string
    {
        $out_entries = [];
        foreach (str_split($str, 128) as $chunk) {
            // Collect sequence of unescaped characters
            $offset = strcspn($chunk, "\n\r\t\x08\f()\\");
            $chunk_out = substr($chunk, 0, $offset);
            while ($offset < strlen($chunk)) {
                $next_code = ord($chunk[$offset++]);
                switch ($next_code) {
                    // "\n" - line feed (LF)
                    case 10:
                        $chunk_out .= '\n';
                        break;
                    // "\r" - carriage return (CR)
                    case 13:
                        $chunk_out .= '\r';
                        break;
                    // "\t" - horizontal tab (HT)
                    case 9:
                        $chunk_out .= '\t';
                        break;
                    // "\b" - backspace (BS)
                    case 8:
                        $chunk_out .= '\b';
                        break;
                    // "\f" - form feed (FF)
                    case 12:
                        $chunk_out .= '\f';
                        break;
                    // '(' - left paranthesis
                    case 40:
                        $chunk_out .= '\(';
                        break;
                    // ')' - right paranthesis
                    case 41:
                        $chunk_out .= '\)';
                        break;
                    // '\' - backslash
                    case 92:
                        $chunk_out .= '\\\\';
                        break;
                    default:
                        // This code is never executed extually
                        //
                        // Don't use non-ASCII characters escaping
                        // if ($nextCode >= 32 && $nextCode <= 126 ) {
                        //     // Visible ASCII symbol
                        //     $chunkEntries[] = chr($nextCode);
                        // } else {
                        //     $chunkEntries[] = sprintf('\\%03o', $nextCode);
                        // }
                        break;
                }
                // Collect sequence of unescaped characters
                $start = $offset;
                $offset += strcspn($chunk, "\n\r\t\x08\f()\\", $offset);
                $chunk_out .= substr($chunk, $start, $offset - $start);
            }
            $out_entries[] = $chunk_out;
        }
        return implode("\\\n", $out_entries);
    }
    /**
     * Unescape string according to the PDF rules
     *
     * @param string $str
     */
    public static function unescape($str): string
    {
        $out_entries = [];
        $offset = 0;
        while ($offset < strlen($str)) {
            // Searche for the next escaped character/sequence
            $escape_char_offset = strpos($str, '\\', $offset);
            if ($escape_char_offset === false || $escape_char_offset == strlen($str) - 1) {
                // There are no escaped characters or '\' char has came at the end of string
                $out_entries[] = substr($str, $offset);
                break;
            } else {
                // Collect unescaped characters sequence
                $out_entries[] = substr($str, $offset, $escape_char_offset - $offset);
                // Go to the escaped character
                $offset = $escape_char_offset + 1;
                switch ($str[$offset]) {
                    // '\\n' - line feed (LF)
                    case 'n':
                        $out_entries[] = "\n";
                        break;
                    // '\\r' - carriage return (CR)
                    case 'r':
                        $out_entries[] = "\r";
                        break;
                    // '\\t' - horizontal tab (HT)
                    case 't':
                        $out_entries[] = "\t";
                        break;
                    // '\\b' - backspace (BS)
                    case 'b':
                        $out_entries[] = "\x08";
                        break;
                    // '\\f' - form feed (FF)
                    case 'f':
                        $out_entries[] = "\f";
                        break;
                    // '\\(' - left paranthesis
                    case '(':
                        $out_entries[] = '(';
                        break;
                    // '\\)' - right paranthesis
                    case ')':
                        $out_entries[] = ')';
                        break;
                    // '\\\\' - backslash
                    case '\\':
                        $out_entries[] = '\\';
                        break;
                    // "\\\n" or "\\\n\r"
                    case "\n":
                        // skip new line symbol
                        if ($str[$offset + 1] == "\r") {
                            $offset++;
                        }
                        break;
                    default:
                        if (strpos('0123456789', $str[$offset]) !== false) {
                            // Character in octal representation
                            // '\\xxx'
                            $next_code = '0' . $str[$offset];
                            if (strpos('0123456789', $str[$offset + 1]) !== false) {
                                $next_code .= $str[++$offset];
                                if (strpos('0123456789', $str[$offset + 1]) !== false) {
                                    $next_code .= $str[++$offset];
                                }
                            }
                            $out_entries[] = chr(octdec($next_code));
                        } else {
                            $out_entries[] = $str[$offset];
                        }
                        break;
                }
                $offset++;
            }
        }
        return implode('', $out_entries);
    }
}