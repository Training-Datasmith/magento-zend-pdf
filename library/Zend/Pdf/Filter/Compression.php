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
 * ASCII85 stream filter
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Filter_Compression implements Zend_Pdf_Filter_Interface
{
    /**
     * Paeth prediction function
     *
     * @param integer $a
     * @param integer $b
     * @param integer $c
     * @return integer
     */
    private static function _paeth($a, $b, $c)
    {
        // $a - left, $b - above, $c - upper left
        $p = $a + $b - $c;
        // initial estimate
        $pa = abs($p - $a);
        // distances to a, b, c
        $pb = abs($p - $b);
        $pc = abs($p - $c);
        // return nearest of a,b,c,
        // breaking ties in order a,b,c.
        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }
        if ($pb <= $pc) {
            return $b;
        }
        return $c;
    }
    /**
     * Get Predictor decode param value
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    private static function _get_predictor_value(array &$params)
    {
        if (isset($params['Predictor'])) {
            $predictor = $params['Predictor'];
            if ($predictor != 1 && $predictor != 2 && $predictor != 10 && $predictor != 11 && $predictor != 12 && $predictor != 13 && $predictor != 14 && $predictor != 15) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Invalid value of \'Predictor\' decode param - ' . $predictor . '.');
            }
            return $predictor;
        }
        return 1;
    }
    /**
     * Get Colors decode param value
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    private static function _get_colors_value(array &$params)
    {
        if (isset($params['Colors'])) {
            $colors = $params['Colors'];
            if ($colors != 1 && $colors != 2 && $colors != 3 && $colors != 4) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Invalid value of \'Color\' decode param - ' . $colors . '.');
            }
            return $colors;
        }
        return 1;
    }
    /**
     * Get BitsPerComponent decode param value
     *
     * @return integer
     * @throws Zend_Pdf_Exception
     */
    private static function _get_bits_per_component_value(array &$params)
    {
        if (isset($params['BitsPerComponent'])) {
            $bits_per_component = $params['BitsPerComponent'];
            if ($bits_per_component != 1 && $bits_per_component != 2 && $bits_per_component != 4 && $bits_per_component != 8 && $bits_per_component != 16) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Invalid value of \'BitsPerComponent\' decode param - ' . $bits_per_component . '.');
            }
            return $bits_per_component;
        }
        return 8;
    }
    /**
     * Get Columns decode param value
     *
     * @return integer
     */
    private static function _get_columns_value(array &$params)
    {
        return $params['Columns'] ?? 1;
    }
    /**
     * Convert stream data according to the filter params set before encoding.
     *
     * @param string $data
     * @param array $params
     * @return string
     * @throws Zend_Pdf_Exception
     */
    protected static function _apply_encode_params($data, $params)
    {
        $predictor = self::_get_predictor_value($params);
        $colors = self::_get_colors_value($params);
        $bits_per_component = self::_get_bits_per_component_value($params);
        $columns = self::_get_columns_value($params);
        /** None of prediction */
        if ($predictor == 1) {
            return $data;
        }
        /** TIFF Predictor 2 */
        if ($predictor == 2) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Not implemented yet');
        }
        /** Optimal PNG prediction */
        if ($predictor == 15) {
            /** Use Paeth prediction as optimal */
            $predictor = 14;
        }
        /** PNG prediction */
        if ($predictor == 10 || $predictor == 11 || $predictor == 12 || $predictor == 13 || $predictor == 14) {
            $predictor -= 10;
            if ($bits_per_component == 16) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('PNG Prediction with bit depth greater than 8 not yet supported.');
            }
            $bits_per_sample = $bits_per_component * $colors;
            $bytes_per_sample = (int) (($bits_per_sample + 7) / 8);
            // (int)ceil(...) emulation
            $bytes_per_row = (int) (($bits_per_sample * $columns + 7) / 8);
            // (int)ceil(...) emulation
            $rows = strlen($data) / $bytes_per_row;
            $output = '';
            $offset = 0;
            if (!is_integer($rows)) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Wrong data length.');
            }
            switch ($predictor) {
                case 0:
                    // None of prediction
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        $output .= substr($data, $offset, $bytes_per_row);
                        $offset += $bytes_per_row;
                    }
                    break;
                case 1:
                    // Sub prediction
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        $last_sample = array_fill(0, $bytes_per_sample, 0);
                        for ($count2 = 0; $count2 < $bytes_per_row; $count2++) {
                            $new_byte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($new_byte - $last_sample[$count2 % $bytes_per_sample]);
                            $last_sample[$count2 % $bytes_per_sample] = $new_byte;
                        }
                    }
                    break;
                case 2:
                    // Up prediction
                    $last_row = array_fill(0, $bytes_per_row, 0);
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        for ($count2 = 0; $count2 < $bytes_per_row; $count2++) {
                            $new_byte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($new_byte - $last_row[$count2]);
                            $last_row[$count2] = $new_byte;
                        }
                    }
                    break;
                case 3:
                    // Average prediction
                    $last_row = array_fill(0, $bytes_per_row, 0);
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        $last_sample = array_fill(0, $bytes_per_sample, 0);
                        for ($count2 = 0; $count2 < $bytes_per_row; $count2++) {
                            $new_byte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($new_byte - floor(($last_sample[$count2 % $bytes_per_sample] + $last_row[$count2]) / 2));
                            $last_sample[$count2 % $bytes_per_sample] = $last_row[$count2] = $new_byte;
                        }
                    }
                    break;
                case 4:
                    // Paeth prediction
                    $last_row = array_fill(0, $bytes_per_row, 0);
                    $current_row = [];
                    for ($count = 0; $count < $rows; $count++) {
                        $output .= chr($predictor);
                        $last_sample = array_fill(0, $bytes_per_sample, 0);
                        for ($count2 = 0; $count2 < $bytes_per_row; $count2++) {
                            $new_byte = ord($data[$offset++]);
                            // Note. chr() automatically cuts input to 8 bit
                            $output .= chr($new_byte - self::_paeth($last_sample[$count2 % $bytes_per_sample], $last_row[$count2], $count2 - $bytes_per_sample < 0 ? 0 : $last_row[$count2 - $bytes_per_sample]));
                            $last_sample[$count2 % $bytes_per_sample] = $current_row[$count2] = $new_byte;
                        }
                        $last_row = $current_row;
                    }
                    break;
            }
            return $output;
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Unknown prediction algorithm - ' . $predictor . '.');
    }
    /**
     * Convert stream data according to the filter params set after decoding.
     *
     * @param string $data
     * @param array $params
     * @return string
     */
    protected static function _apply_decode_params($data, $params)
    {
        $predictor = self::_get_predictor_value($params);
        $colors = self::_get_colors_value($params);
        $bits_per_component = self::_get_bits_per_component_value($params);
        $columns = self::_get_columns_value($params);
        /** None of prediction */
        if ($predictor == 1) {
            return $data;
        }
        /** TIFF Predictor 2 */
        if ($predictor == 2) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Not implemented yet');
        }
        /**
         * PNG prediction
         * Prediction code is duplicated on each row.
         * Thus all cases can be brought to one
         */
        if ($predictor == 10 || $predictor == 11 || $predictor == 12 || $predictor == 13 || $predictor == 14 || $predictor == 15) {
            $bits_per_sample = $bits_per_component * $colors;
            $bytes_per_sample = ceil($bits_per_sample / 8);
            $bytes_per_row = ceil($bits_per_sample * $columns / 8);
            $rows = ceil(strlen($data) / ($bytes_per_row + 1));
            $output = '';
            $offset = 0;
            $last_row = array_fill(0, $bytes_per_row, 0);
            for ($count = 0; $count < $rows; $count++) {
                $last_sample = array_fill(0, $bytes_per_sample, 0);
                switch (ord($data[$offset++])) {
                    case 0:
                        // None of prediction
                        $output .= substr($data, $offset, $bytes_per_row);
                        for ($count2 = 0; $count2 < $bytes_per_row && $offset < strlen($data); $count2++) {
                            $last_sample[$count2 % $bytes_per_sample] = $last_row[$count2] = ord($data[$offset++]);
                        }
                        break;
                    case 1:
                        // Sub prediction
                        for ($count2 = 0; $count2 < $bytes_per_row && $offset < strlen($data); $count2++) {
                            $decoded_byte = ord($data[$offset++]) + $last_sample[$count2 % $bytes_per_sample] & 0xff;
                            $last_sample[$count2 % $bytes_per_sample] = $last_row[$count2] = $decoded_byte;
                            $output .= chr($decoded_byte);
                        }
                        break;
                    case 2:
                        // Up prediction
                        for ($count2 = 0; $count2 < $bytes_per_row && $offset < strlen($data); $count2++) {
                            $decoded_byte = ord($data[$offset++]) + $last_row[$count2] & 0xff;
                            $last_sample[$count2 % $bytes_per_sample] = $last_row[$count2] = $decoded_byte;
                            $output .= chr($decoded_byte);
                        }
                        break;
                    case 3:
                        // Average prediction
                        for ($count2 = 0; $count2 < $bytes_per_row && $offset < strlen($data); $count2++) {
                            $decoded_byte = ord($data[$offset++]) + floor(($last_sample[$count2 % $bytes_per_sample] + $last_row[$count2]) / 2) & 0xff;
                            $last_sample[$count2 % $bytes_per_sample] = $last_row[$count2] = $decoded_byte;
                            $output .= chr($decoded_byte);
                        }
                        break;
                    case 4:
                        // Paeth prediction
                        $current_row = [];
                        for ($count2 = 0; $count2 < $bytes_per_row && $offset < strlen($data); $count2++) {
                            $decoded_byte = ord($data[$offset++]) + self::_paeth($last_sample[$count2 % $bytes_per_sample], $last_row[$count2], $count2 - $bytes_per_sample < 0 ? 0 : $last_row[$count2 - $bytes_per_sample]) & 0xff;
                            $last_sample[$count2 % $bytes_per_sample] = $current_row[$count2] = $decoded_byte;
                            $output .= chr($decoded_byte);
                        }
                        $last_row = $current_row;
                        break;
                    default:
                        #require_once 'Zend/Pdf/Exception.php';
                        throw new Zend_Pdf_Exception('Unknown prediction tag.');
                }
            }
            return $output;
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Unknown prediction algorithm - ' . $predictor . '.');
    }
}