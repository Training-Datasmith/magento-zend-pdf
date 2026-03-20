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
/** Zend_Pdf_Filter_Interface */
#require_once 'Zend/Pdf/Filter/Interface.php';
/**
 * AsciiHex stream filter
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_filter_ascii_Hex implements Zend_Pdf_Filter_Interface
{
    /**
     * Encode data
     *
     * @param string $data
     * @param array $params
     * @throws Zend_Pdf_Exception
     */
    public static function encode($data, $params = null): string
    {
        return bin2hex($data) . '>';
    }
    /**
     * Decode data
     *
     * @param string $data
     * @param array $params
     * @throws Zend_Pdf_Exception
     */
    public static function decode($data, $params = null): string
    {
        $output = '';
        $odd_code = true;
        $comment_mode = false;
        for ($count = 0; $count < strlen($data) && $data[$count] != '>'; $count++) {
            $char_code = ord($data[$count]);
            if ($comment_mode) {
                if ($char_code == 0xa || $char_code == 0xd) {
                    $comment_mode = false;
                }
                continue;
            }
            switch ($char_code) {
                //Skip white space
                case 0x0:
                // null character
                // fall through to next case
                case 0x9:
                // Tab
                // fall through to next case
                case 0xa:
                // Line feed
                // fall through to next case
                case 0xc:
                // Form Feed
                // fall through to next case
                case 0xd:
                // Carriage return
                // fall through to next case
                case 0x20:
                    // Space
                    // Do nothing
                    break;
                case 0x25:
                    // '%'
                    // Switch to comment mode
                    $comment_mode = true;
                    break;
                default:
                    if ($char_code >= 0x30 && $char_code <= 0x39) {
                        $code = $char_code - 0x30;
                    } elseif ($char_code >= 0x41 && $char_code <= 0x46) {
                        $code = $char_code - 0x37;
                    } elseif ($char_code >= 0x61 && $char_code <= 0x66) {
                        $code = $char_code - 0x57;
                    } else {
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception('Wrong character in a encoded stream');
                    }
                    if ($odd_code) {
                        // Odd pass. Store hex digit for next pass
                        // Scope of $hexCodeHigh variable is whole function
                        $hex_code_high = $code;
                    } else {
                        // Even pass.
                        // Add decoded character to the output
                        // ($hexCodeHigh is stored in previous pass)
                        $output .= chr($hex_code_high * 16 + $code);
                    }
                    $odd_code = !$odd_code;
                    break;
            }
        }
        /* Check that stream is terminated by End Of Data marker */
        if ($data[$count] != '>') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Wrong encoded stream End Of Data marker.');
        }
        /* Last '0' character is omitted */
        if (!$odd_code) {
            $output .= chr($hex_code_high * 16);
        }
        return $output;
    }
}