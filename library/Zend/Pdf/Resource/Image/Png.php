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
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String/Binary.php';
/** Zend_Pdf_Resource_Image */
#require_once 'Zend/Pdf/Resource/Image.php';
/**
 * PNG image
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Image_Png extends Zend_Pdf_Resource_Image
{
    public const PNG_COMPRESSION_DEFAULT_STRATEGY = 0;
    public const PNG_COMPRESSION_FILTERED = 1;
    public const PNG_COMPRESSION_HUFFMAN_ONLY = 2;
    public const PNG_COMPRESSION_RLE = 3;
    public const PNG_FILTER_NONE = 0;
    public const PNG_FILTER_SUB = 1;
    public const PNG_FILTER_UP = 2;
    public const PNG_FILTER_AVERAGE = 3;
    public const PNG_FILTER_PAETH = 4;
    public const PNG_INTERLACING_DISABLED = 0;
    public const PNG_INTERLACING_ENABLED = 1;
    public const PNG_CHANNEL_GRAY = 0;
    public const PNG_CHANNEL_RGB = 2;
    public const PNG_CHANNEL_INDEXED = 3;
    public const PNG_CHANNEL_GRAY_ALPHA = 4;
    public const PNG_CHANNEL_RGB_ALPHA = 6;
    protected $_width;
    protected $_height;
    protected $_image_properties;
    /**
     * Object constructor
     *
     * @param string $imageFileName
     * @throws Zend_Pdf_Exception
     * @todo Add compression conversions to support compression strategys other than PNG_COMPRESSION_DEFAULT_STRATEGY.
     * @todo Add pre-compression filtering.
     * @todo Add interlaced image handling.
     * @todo Add support for 16-bit images. Requires PDF version bump to 1.5 at least.
     * @todo Add processing for all PNG chunks defined in the spec. gAMA etc.
     * @todo Fix tRNS chunk support for Indexed Images to a SMask.
     */
    public function __construct($image_file_name)
    {
        if (($image_file = @fopen($image_file_name, 'rb')) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Can not open '{$image_file_name}' file for reading.");
        }
        parent::__construct();
        //Check if the file is a PNG
        fseek($image_file, 1, SEEK_CUR);
        //First signature byte (%)
        if ('PNG' != fread($image_file, 3)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Image is not a PNG');
        }
        fseek($image_file, 12, SEEK_CUR);
        //Signature bytes (Includes the IHDR chunk) IHDR processed linerarly because it doesnt contain a variable chunk length
        $wtmp = unpack('Ni', fread($image_file, 4));
        //Unpack a 4-Byte Long
        $width = $wtmp['i'];
        $htmp = unpack('Ni', fread($image_file, 4));
        $height = $htmp['i'];
        $bits = ord(fread($image_file, 1));
        //Higher than 8 bit depths are only supported in later versions of PDF.
        $color = ord(fread($image_file, 1));
        $compression = ord(fread($image_file, 1));
        $prefilter = ord(fread($image_file, 1));
        if (($interlacing = ord(fread($image_file, 1))) != Zend_Pdf_Resource_Image_Png::PNG_INTERLACING_DISABLED) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Only non-interlaced images are currently supported.');
        }
        $this->_width = $width;
        $this->_height = $height;
        $this->_image_properties = [];
        $this->_image_properties['bitDepth'] = $bits;
        $this->_image_properties['pngColorType'] = $color;
        $this->_image_properties['pngFilterType'] = $prefilter;
        $this->_image_properties['pngCompressionType'] = $compression;
        $this->_image_properties['pngInterlacingType'] = $interlacing;
        fseek($image_file, 4, SEEK_CUR);
        //4 Byte Ending Sequence
        $image_data = '';
        /*
         * The following loop processes PNG chunks. 4 Byte Longs are packed first give the chunk length
         * followed by the chunk signature, a four byte code. IDAT and IEND are manditory in any PNG.
         */
        while (!feof($image_file)) {
            $chunk_length_bytes = fread($image_file, 4);
            if ($chunk_length_bytes === false) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Error ocuured while image file reading.');
            }
            $chunk_lengthtmp = unpack('Ni', $chunk_length_bytes);
            $chunk_length = $chunk_lengthtmp['i'];
            $chunk_type = fread($image_file, 4);
            switch ($chunk_type) {
                case 'IDAT':
                    //Image Data
                    /*
                     * Reads the actual image data from the PNG file. Since we know at this point that the compression
                     * strategy is the default strategy, we also know that this data is Zip compressed. We will either copy
                     * the data directly to the PDF and provide the correct FlateDecode predictor, or decompress the data
                     * decode the filters and output the data as a raw pixel map.
                     */
                    $image_data .= fread($image_file, $chunk_length);
                    fseek($image_file, 4, SEEK_CUR);
                    break;
                case 'PLTE':
                    //Palette
                    $palette_data = fread($image_file, $chunk_length);
                    fseek($image_file, 4, SEEK_CUR);
                    break;
                case 'tRNS':
                    //Basic (non-alpha channel) transparency.
                    $trns_data = fread($image_file, $chunk_length);
                    switch ($color) {
                        case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY:
                            $base_color = ord(substr($trns_data, 1, 1));
                            $transparency_data = [new Zend_Pdf_Element_Numeric($base_color), new Zend_Pdf_Element_Numeric($base_color)];
                            break;
                        case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB:
                            $red = ord(substr($trns_data, 1, 1));
                            $green = ord(substr($trns_data, 3, 1));
                            $blue = ord(substr($trns_data, 5, 1));
                            $transparency_data = [new Zend_Pdf_Element_Numeric($red), new Zend_Pdf_Element_Numeric($red), new Zend_Pdf_Element_Numeric($green), new Zend_Pdf_Element_Numeric($green), new Zend_Pdf_Element_Numeric($blue), new Zend_Pdf_Element_Numeric($blue)];
                            break;
                        case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_INDEXED:
                            //Find the first transparent color in the index, we will mask that. (This is a bit of a hack. This should be a SMask and mask all entries values).
                            if (($trns_idx = strpos($trns_data, "\x00")) !== false) {
                                $transparency_data = [new Zend_Pdf_Element_Numeric($trns_idx), new Zend_Pdf_Element_Numeric($trns_idx)];
                            }
                            break;
                        case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY_ALPHA:
                        // Fall through to the next case
                        case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA:
                            #require_once 'Zend/Pdf/Exception.php';
                            throw new Zend_Pdf_Exception('tRNS chunk illegal for Alpha Channel Images');
                    }
                    fseek($image_file, 4, SEEK_CUR);
                    //4 Byte Ending Sequence
                    break;
                case 'IEND':
                    break 2;
                //End the loop too
                default:
                    fseek($image_file, $chunk_length + 4, SEEK_CUR);
                    //Skip the section
                    break;
            }
        }
        fclose($image_file);
        $compressed = true;
        $image_data_tmp = '';
        $smask_data = '';
        switch ($color) {
            case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB:
                $color_space = new Zend_Pdf_Element_Name('DeviceRGB');
                break;
            case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY:
                $color_space = new Zend_Pdf_Element_Name('DeviceGray');
                break;
            case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_INDEXED:
                if (empty($palette_data)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('PNG Corruption: No palette data read for indexed type PNG.');
                }
                $color_space = new Zend_Pdf_Element_Array();
                $color_space->items[] = new Zend_Pdf_Element_Name('Indexed');
                $color_space->items[] = new Zend_Pdf_Element_Name('DeviceRGB');
                $color_space->items[] = new Zend_Pdf_Element_Numeric(strlen($palette_data) / 3 - 1);
                $palette_object = $this->_object_factory->new_object(new Zend_Pdf_Element_String_Binary($palette_data));
                $color_space->items[] = $palette_object;
                break;
            case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_GRAY_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the Gray data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if ($bits > 8) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Alpha PNGs with bit depth > 8 are not yet supported');
                }
                $color_space = new Zend_Pdf_Element_Name('DeviceGray');
                #require_once 'Zend/Pdf/ElementFactory.php';
                $decoding_obj_factory = Zend_pdf_element_Factory::create_factory(1);
                $decoding_stream = $decoding_obj_factory->new_stream_object($image_data);
                $decoding_stream->dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
                $decoding_stream->dictionary->decode_parms = new Zend_Pdf_Element_Dictionary();
                $decoding_stream->dictionary->decode_parms->Predictor = new Zend_Pdf_Element_Numeric(15);
                $decoding_stream->dictionary->decode_parms->Columns = new Zend_Pdf_Element_Numeric($width);
                $decoding_stream->dictionary->decode_parms->Colors = new Zend_Pdf_Element_Numeric(2);
                //GreyAlpha
                $decoding_stream->dictionary->decode_parms->bits_per_component = new Zend_Pdf_Element_Numeric($bits);
                $decoding_stream->skip_filters();
                $png_data_raw_decoded = $decoding_stream->value;
                //Iterate every pixel and copy out gray data and alpha channel (this will be slow)
                for ($pixel = 0, $pixelcount = $width * $height; $pixel < $pixelcount; $pixel++) {
                    $image_data_tmp .= $png_data_raw_decoded[$pixel * 2];
                    $smask_data .= $png_data_raw_decoded[$pixel * 2 + 1];
                }
                $compressed = false;
                $image_data = $image_data_tmp;
                //Overwrite image data with the gray channel without alpha
                break;
            case Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA:
                /*
                 * To decode PNG's with alpha data we must create two images from one. One image will contain the RGB data
                 * the other will contain the Gray transparency overlay data. The former will become the object data and the latter
                 * will become the Shadow Mask (SMask).
                 */
                if ($bits > 8) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Alpha PNGs with bit depth > 8 are not yet supported');
                }
                $color_space = new Zend_Pdf_Element_Name('DeviceRGB');
                #require_once 'Zend/Pdf/ElementFactory.php';
                $decoding_obj_factory = Zend_pdf_element_Factory::create_factory(1);
                $decoding_stream = $decoding_obj_factory->new_stream_object($image_data);
                $decoding_stream->dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
                $decoding_stream->dictionary->decode_parms = new Zend_Pdf_Element_Dictionary();
                $decoding_stream->dictionary->decode_parms->Predictor = new Zend_Pdf_Element_Numeric(15);
                $decoding_stream->dictionary->decode_parms->Columns = new Zend_Pdf_Element_Numeric($width);
                $decoding_stream->dictionary->decode_parms->Colors = new Zend_Pdf_Element_Numeric(4);
                //RGBA
                $decoding_stream->dictionary->decode_parms->bits_per_component = new Zend_Pdf_Element_Numeric($bits);
                $decoding_stream->skip_filters();
                $png_data_raw_decoded = $decoding_stream->value;
                //Iterate every pixel and copy out rgb data and alpha channel (this will be slow)
                for ($pixel = 0, $pixelcount = $width * $height; $pixel < $pixelcount; $pixel++) {
                    $image_data_tmp .= $png_data_raw_decoded[$pixel * 4] . $png_data_raw_decoded[$pixel * 4 + 1] . $png_data_raw_decoded[$pixel * 4 + 2];
                    $smask_data .= $png_data_raw_decoded[$pixel * 4 + 3];
                }
                $compressed = false;
                $image_data = $image_data_tmp;
                //Overwrite image data with the RGB channel without alpha
                break;
            default:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('PNG Corruption: Invalid color space.');
        }
        if (empty($image_data)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Corrupt PNG Image. Mandatory IDAT chunk not found.');
        }
        $image_dictionary = $this->_resource->dictionary;
        if (!empty($smask_data)) {
            /*
             * Includes the Alpha transparency data as a Gray Image, then assigns the image as the Shadow Mask for the main image data.
             */
            $smask_stream = $this->_object_factory->new_stream_object($smask_data);
            $smask_stream->dictionary->Type = new Zend_Pdf_Element_Name('XObject');
            $smask_stream->dictionary->Subtype = new Zend_Pdf_Element_Name('Image');
            $smask_stream->dictionary->Width = new Zend_Pdf_Element_Numeric($width);
            $smask_stream->dictionary->Height = new Zend_Pdf_Element_Numeric($height);
            $smask_stream->dictionary->color_space = new Zend_Pdf_Element_Name('DeviceGray');
            $smask_stream->dictionary->bits_per_component = new Zend_Pdf_Element_Numeric($bits);
            $image_dictionary->s_mask = $smask_stream;
            // Encode stream with FlateDecode filter
            $smask_stream_decode_parms = [];
            $smask_stream_decode_parms['Predictor'] = new Zend_Pdf_Element_Numeric(15);
            $smask_stream_decode_parms['Columns'] = new Zend_Pdf_Element_Numeric($width);
            $smask_stream_decode_parms['Colors'] = new Zend_Pdf_Element_Numeric(1);
            $smask_stream_decode_parms['BitsPerComponent'] = new Zend_Pdf_Element_Numeric(8);
            $smask_stream->dictionary->decode_parms = new Zend_Pdf_Element_Dictionary($smask_stream_decode_parms);
            $smask_stream->dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
        }
        if (!empty($transparency_data)) {
            //This is experimental and not properly tested.
            $image_dictionary->Mask = new Zend_Pdf_Element_Array($transparency_data);
        }
        $image_dictionary->Width = new Zend_Pdf_Element_Numeric($width);
        $image_dictionary->Height = new Zend_Pdf_Element_Numeric($height);
        $image_dictionary->color_space = $color_space;
        $image_dictionary->bits_per_component = new Zend_Pdf_Element_Numeric($bits);
        $image_dictionary->Filter = new Zend_Pdf_Element_Name('FlateDecode');
        $decode_parms = [];
        $decode_parms['Predictor'] = new Zend_Pdf_Element_Numeric(15);
        // Optimal prediction
        $decode_parms['Columns'] = new Zend_Pdf_Element_Numeric($width);
        $decode_parms['Colors'] = new Zend_Pdf_Element_Numeric($color == Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB || $color == Zend_Pdf_Resource_Image_Png::PNG_CHANNEL_RGB_ALPHA ? 3 : 1);
        $decode_parms['BitsPerComponent'] = new Zend_Pdf_Element_Numeric($bits);
        $image_dictionary->decode_parms = new Zend_Pdf_Element_Dictionary($decode_parms);
        //Include only the image IDAT section data.
        $this->_resource->value = $image_data;
        //Skip double compression
        if ($compressed) {
            $this->_resource->skip_filters();
        }
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