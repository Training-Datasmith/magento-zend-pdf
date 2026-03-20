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
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Resource_Image */
#require_once 'Zend/Pdf/Resource/Image.php';
/**
 * JPEG image
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Image_Jpeg extends Zend_Pdf_Resource_Image
{
    protected $_width;
    protected $_height;
    protected $_image_properties;
    /**
     * Object constructor
     *
     * @param string $imageFileName
     * @throws Zend_Pdf_Exception
     */
    public function __construct($image_file_name)
    {
        if (!function_exists('gd_info')) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Image extension is not installed.');
        }
        $gd_options = gd_info();
        if ((!isset($gd_options['JPG Support']) || $gd_options['JPG Support'] != true) && (!isset($gd_options['JPEG Support']) || $gd_options['JPEG Support'] != true)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('JPG support is not configured properly.');
        }
        if (($image_info = getimagesize($image_file_name)) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Corrupted image or image doesn\'t exist.');
        }
        if ($image_info[2] != IMAGETYPE_JPEG && $image_info[2] != IMAGETYPE_JPEG2000) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('ImageType is not JPG');
        }
        parent::__construct();
        switch ($image_info['channels']) {
            case 3:
                $color_space = 'DeviceRGB';
                break;
            case 4:
                $color_space = 'DeviceCMYK';
                break;
            default:
                $color_space = 'DeviceGray';
                break;
        }
        $image_dictionary = $this->_resource->dictionary;
        $image_dictionary->Width = new Zend_Pdf_Element_Numeric($image_info[0]);
        $image_dictionary->Height = new Zend_Pdf_Element_Numeric($image_info[1]);
        $image_dictionary->color_space = new Zend_Pdf_Element_Name($color_space);
        $image_dictionary->bits_per_component = new Zend_Pdf_Element_Numeric($image_info['bits']);
        if ($image_info[2] == IMAGETYPE_JPEG) {
            $image_dictionary->Filter = new Zend_Pdf_Element_Name('DCTDecode');
        } elseif ($image_info[2] == IMAGETYPE_JPEG2000) {
            $image_dictionary->Filter = new Zend_Pdf_Element_Name('JPXDecode');
        }
        if (($image_file = @fopen($image_file_name, 'rb')) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Can not open '{$image_file_name}' file for reading.");
        }
        $byte_count = filesize($image_file_name);
        $this->_resource->value = '';
        while ($byte_count > 0 && !feof($image_file)) {
            $next_block = fread($image_file, $byte_count);
            if ($next_block === false) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception("Error occured while '{$image_file_name}' file reading.");
            }
            $this->_resource->value .= $next_block;
            $byte_count -= strlen($next_block);
        }
        if ($byte_count != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Error occured while '{$image_file_name}' file reading.");
        }
        fclose($image_file);
        $this->_resource->skip_filters();
        $this->_width = $image_info[0];
        $this->_height = $image_info[1];
        $this->_image_properties = [];
        $this->_image_properties['bitDepth'] = $image_info['bits'];
        $this->_image_properties['jpegImageType'] = $image_info[2];
        $this->_image_properties['jpegColorType'] = $image_info['channels'];
    }
    /**
     * Image width
     */
    public function get_pixel_width()
    {
        return $this->_width;
    }
    /**
     * Image height
     */
    public function get_pixel_height()
    {
        return $this->_height;
    }
    /**
     * Image properties
     */
    public function get_properties()
    {
        return $this->_image_properties;
    }
}