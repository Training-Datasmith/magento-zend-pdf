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
 * RunLength stream filter
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_filter_run_Length implements Zend_Pdf_Filter_Interface
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
        $output = '';
        $chain_start_offset = 0;
        $offset = 0;
        while ($offset < strlen($data)) {
            // Do not encode 2 char chains since they produce 2 char run sequence,
            // but it takes more time to decode such output (because of processing additional run)
            if (($repeated_char_chain_length = strspn($data, $data[$offset], $offset + 1, 127) + 1) > 2) {
                if ($chain_start_offset != $offset) {
                    // Drop down previouse (non-repeatable chars) run
                    $output .= chr($offset - $chain_start_offset - 1) . substr($data, $chain_start_offset, $offset - $chain_start_offset);
                }
                $output .= chr(257 - $repeated_char_chain_length) . $data[$offset];
                $offset += $repeated_char_chain_length;
                $chain_start_offset = $offset;
            } else {
                $offset++;
                if ($offset - $chain_start_offset == 128) {
                    // Maximum run length is reached
                    // Drop down non-repeatable chars run
                    $output .= "" . substr($data, $chain_start_offset, 128);
                    $chain_start_offset = $offset;
                }
            }
        }
        if ($chain_start_offset != $offset) {
            // Drop down non-repeatable chars run
            $output .= chr($offset - $chain_start_offset - 1) . substr($data, $chain_start_offset, $offset - $chain_start_offset);
        }
        return $output . "\x80";
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
        $data_length = strlen($data);
        $output = '';
        $offset = 0;
        while ($offset < $data_length) {
            $length = ord($data[$offset]);
            $offset++;
            if ($length == 128) {
                // EOD byte
                break;
            } elseif ($length < 128) {
                $length++;
                $output .= substr($data, $offset, $length);
                $offset += $length;
            } else {
                $output .= str_repeat($data[$offset], 257 - $length);
                $offset++;
            }
        }
        return $output;
    }
}