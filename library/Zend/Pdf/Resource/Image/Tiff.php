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
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Resource_Image */
#require_once 'Zend/Pdf/Resource/Image.php';
/**
 * TIFF image
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Resource_Image_Tiff extends Zend_Pdf_Resource_Image
{
    public const TIFF_FIELD_TYPE_BYTE = 1;
    public const TIFF_FIELD_TYPE_ASCII = 2;
    public const TIFF_FIELD_TYPE_SHORT = 3;
    public const TIFF_FIELD_TYPE_LONG = 4;
    public const TIFF_FIELD_TYPE_RATIONAL = 5;
    public const TIFF_TAG_IMAGE_WIDTH = 256;
    public const TIFF_TAG_IMAGE_LENGTH = 257;
    //Height
    public const TIFF_TAG_BITS_PER_SAMPLE = 258;
    public const TIFF_TAG_COMPRESSION = 259;
    public const TIFF_TAG_PHOTOMETRIC_INTERPRETATION = 262;
    public const TIFF_TAG_STRIP_OFFSETS = 273;
    public const TIFF_TAG_SAMPLES_PER_PIXEL = 277;
    public const TIFF_TAG_STRIP_BYTE_COUNTS = 279;
    public const TIFF_COMPRESSION_UNCOMPRESSED = 1;
    public const TIFF_COMPRESSION_CCITT1D = 2;
    public const TIFF_COMPRESSION_GROUP_3_FAX = 3;
    public const TIFF_COMPRESSION_GROUP_4_FAX = 4;
    public const TIFF_COMPRESSION_LZW = 5;
    public const TIFF_COMPRESSION_JPEG = 6;
    public const TIFF_COMPRESSION_FLATE = 8;
    public const TIFF_COMPRESSION_FLATE_OBSOLETE_CODE = 32946;
    public const TIFF_COMPRESSION_PACKBITS = 32773;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_WHITE_IS_ZERO = 0;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_BLACK_IS_ZERO = 1;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_RGB = 2;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_RGB_INDEXED = 3;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_CMYK = 5;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_YCBCR = 6;
    public const TIFF_PHOTOMETRIC_INTERPRETATION_CIELAB = 8;
    protected $_width;
    protected $_height;
    protected $_image_properties;
    protected $_endian_type;
    protected $_file_size;
    protected $_bits_per_sample;
    protected $_compression;
    protected $_filter;
    protected $_color_code;
    protected $_white_is_zero;
    protected $_black_is_zero;
    protected $_color_space;
    protected $_image_data_offset;
    protected $_image_data_length;
    public const TIFF_ENDIAN_BIG = 0;
    public const TIFF_ENDIAN_LITTLE = 1;
    public const UNPACK_TYPE_BYTE = 0;
    public const UNPACK_TYPE_SHORT = 1;
    public const UNPACK_TYPE_LONG = 2;
    public const UNPACK_TYPE_RATIONAL = 3;
    /**
     * Byte unpacking function
     *
     * Makes it possible to unpack bytes in one statement for enhanced logic readability.
     *
     * @param int $type
     * @param string $bytes
     * @throws Zend_Pdf_Exception
     */
    protected function unpack_bytes($type, $bytes)
    {
        if (!isset($this->_endian_type)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('The unpackBytes function can only be used after the endianness of the file is known');
        }
        switch ($type) {
            case Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_BYTE:
                $format = 'C';
                $unpacked = unpack($format, $bytes);
                return $unpacked[1];
            case Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT:
                $format = $this->_endian_type == Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE ? 'v' : 'n';
                $unpacked = unpack($format, $bytes);
                return $unpacked[1];
            case Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG:
                $format = $this->_endian_type == Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE ? 'V' : 'N';
                $unpacked = unpack($format, $bytes);
                return $unpacked[1];
            case Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_RATIONAL:
                $format = $this->_endian_type == Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE ? 'V2' : 'N2';
                $unpacked = unpack($format, $bytes);
                return $unpacked[1] / $unpacked[2];
        }
    }
    /**
     * Object constructor
     *
     * @param string $imageFileName
     * @throws Zend_Pdf_Exception
     */
    public function __construct($image_file_name)
    {
        if (($image_file = @fopen($image_file_name, 'rb')) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Can not open '{$image_file_name}' file for reading.");
        }
        $byte_order_indicator = fread($image_file, 2);
        if ($byte_order_indicator == 'II') {
            $this->_endian_type = Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE;
        } elseif ($byte_order_indicator == 'MM') {
            $this->_endian_type = Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_BIG;
        } else {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Not a tiff file or Tiff corrupt. No byte order indication found');
        }
        $version = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, fread($image_file, 2));
        if ($version != 42) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Not a tiff file or Tiff corrupt. Incorrect version number.');
        }
        $ifd_offset = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG, fread($image_file, 4));
        $file_stats = fstat($image_file);
        $this->_file_size = $file_stats['size'];
        /*
         * Tiff files are stored as a series of Image File Directories (IFD) each direcctory
         * has a specific number of entries each 12 bytes in length. At the end of the directories
         * is four bytes pointing to the offset of the next IFD.
         */
        while ($ifd_offset > 0) {
            if (fseek($image_file, $ifd_offset, SEEK_SET) == -1 || $ifd_offset + 2 >= $this->_file_size) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Could not seek to the image file directory as indexed by the file. Likely cause is TIFF corruption. Offset: ' . $ifd_offset);
            }
            $num_dir_entries = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, fread($image_file, 2));
            /*
             * Since we now know how many entries are in this (IFD) we can extract the data.
             * The format of a TIFF directory entry is:
             *
             * 2 bytes (short) tag code; See TIFF_TAG constants at the top for supported values. (There are many more in the spec)
             * 2 bytes (short) field type
             * 4 bytes (long) number of values, or value count.
             * 4 bytes (mixed) data if the data will fit into 4 bytes or an offset if the data is too large.
             */
            for ($dir_entry_idx = 1; $dir_entry_idx <= $num_dir_entries; $dir_entry_idx++) {
                $tag = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, fread($image_file, 2));
                $field_type = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, fread($image_file, 2));
                $value_count = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG, fread($image_file, 4));
                switch ($field_type) {
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_BYTE:
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_ASCII:
                        $field_length = $value_count;
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_SHORT:
                        $field_length = $value_count * 2;
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_LONG:
                        $field_length = $value_count * 4;
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_RATIONAL:
                        $field_length = $value_count * 8;
                        break;
                    default:
                        $field_length = $value_count;
                }
                $offset_bytes = fread($image_file, 4);
                if ($field_length <= 4) {
                    switch ($field_type) {
                        case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_BYTE:
                            $value = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_BYTE, $offset_bytes);
                            break;
                        case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_ASCII:
                        //Fall through to next case
                        case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_LONG:
                            $value = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG, $offset_bytes);
                            break;
                        case Zend_Pdf_Resource_Image_Tiff::TIFF_FIELD_TYPE_SHORT:
                        //Fall through to next case
                        default:
                            $value = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, $offset_bytes);
                    }
                } else {
                    $ref_offset = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG, $offset_bytes);
                }
                /*
                 * Linear tag processing is probably not the best way to do this. I've processed the tags according to the
                 * Tiff 6 specification and make some assumptions about when tags will be < 4 bytes and fit into $value and when
                 * they will be > 4 bytes and require seek/extraction of the offset. Same goes for extracting arrays of data, like
                 * the data offsets and length. This should be fixed in the future.
                 */
                switch ($tag) {
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_IMAGE_WIDTH:
                        $this->_width = $value;
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_IMAGE_LENGTH:
                        $this->_height = $value;
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_BITS_PER_SAMPLE:
                        if ($value_count > 1) {
                            $fp = ftell($image_file);
                            fseek($image_file, $ref_offset, SEEK_SET);
                            $this->_bits_per_sample = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_SHORT, fread($image_file, 2));
                            fseek($image_file, $fp, SEEK_SET);
                        } else {
                            $this->_bits_per_sample = $value;
                        }
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_COMPRESSION:
                        $this->_compression = $value;
                        switch ($value) {
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_UNCOMPRESSED:
                                $this->_filter = 'None';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_CCITT1D:
                            //Fall through to next case
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_GROUP_3_FAX:
                            //Fall through to next case
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_GROUP_4_FAX:
                                $this->_filter = 'CCITTFaxDecode';
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('CCITTFaxDecode Compression Mode Not Currently Supported');
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_LZW:
                                $this->_filter = 'LZWDecode';
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('LZWDecode Compression Mode Not Currently Supported');
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_JPEG:
                                $this->_filter = 'DCTDecode';
                                //Should work, doesnt...
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('JPEG Compression Mode Not Currently Supported');
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_FLATE:
                            //fall through to next case
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_FLATE_OBSOLETE_CODE:
                                $this->_filter = 'FlateDecode';
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('ZIP/Flate Compression Mode Not Currently Supported');
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_COMPRESSION_PACKBITS:
                                $this->_filter = 'RunLengthDecode';
                                break;
                        }
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_PHOTOMETRIC_INTERPRETATION:
                        $this->_color_code = $value;
                        $this->_white_is_zero = false;
                        $this->_black_is_zero = false;
                        switch ($value) {
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_WHITE_IS_ZERO:
                                $this->_white_is_zero = true;
                                $this->_color_space = 'DeviceGray';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_BLACK_IS_ZERO:
                                $this->_black_is_zero = true;
                                $this->_color_space = 'DeviceGray';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_YCBCR:
                            //fall through to next case
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_RGB:
                                $this->_color_space = 'DeviceRGB';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_RGB_INDEXED:
                                $this->_color_space = 'Indexed';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_CMYK:
                                $this->_color_space = 'DeviceCMYK';
                                break;
                            case Zend_Pdf_Resource_Image_Tiff::TIFF_PHOTOMETRIC_INTERPRETATION_CIELAB:
                                $this->_color_space = 'Lab';
                                break;
                            default:
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('TIFF: Unknown or Unsupported Color Type: ' . $value);
                        }
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_STRIP_OFFSETS:
                        if ($value_count > 1) {
                            $format = $this->_endian_type == Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE ? 'V*' : 'N*';
                            $fp = ftell($image_file);
                            fseek($image_file, $ref_offset, SEEK_SET);
                            $strip_offsets_bytes = fread($image_file, $field_length);
                            $this->_image_data_offset = unpack($format, $strip_offsets_bytes);
                            fseek($image_file, $fp, SEEK_SET);
                        } else {
                            $this->_image_data_offset = $value;
                        }
                        break;
                    case Zend_Pdf_Resource_Image_Tiff::TIFF_TAG_STRIP_BYTE_COUNTS:
                        if ($value_count > 1) {
                            $format = $this->_endian_type == Zend_Pdf_Resource_Image_Tiff::TIFF_ENDIAN_LITTLE ? 'V*' : 'N*';
                            $fp = ftell($image_file);
                            fseek($image_file, $ref_offset, SEEK_SET);
                            $strip_byte_counts_bytes = fread($image_file, $field_length);
                            $this->_image_data_length = unpack($format, $strip_byte_counts_bytes);
                            fseek($image_file, $fp, SEEK_SET);
                        } else {
                            $this->_image_data_length = $value;
                        }
                        break;
                    default:
                }
            }
            $ifd_offset = $this->unpack_bytes(Zend_Pdf_Resource_Image_Tiff::UNPACK_TYPE_LONG, fread($image_file, 4));
        }
        if (!isset($this->_image_data_offset) || !isset($this->_image_data_length)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('TIFF: The image processed did not contain image data as expected.');
        }
        $image_data_bytes = '';
        if (is_array($this->_image_data_offset)) {
            if (!is_array($this->_image_data_length)) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('TIFF: The image contained multiple data offsets but not multiple data lengths. Tiff may be corrupt.');
            }
            foreach ($this->_image_data_offset as $idx => $offset) {
                fseek($image_file, $this->_image_data_offset[$idx], SEEK_SET);
                $image_data_bytes .= fread($image_file, $this->_image_data_length[$idx]);
            }
        } else {
            fseek($image_file, $this->_image_data_offset, SEEK_SET);
            $image_data_bytes = fread($image_file, $this->_image_data_length);
        }
        if ($image_data_bytes === '') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('TIFF: No data. Image Corruption');
        }
        fclose($image_file);
        parent::__construct();
        $image_dictionary = $this->_resource->dictionary;
        if (!isset($this->_width) || !isset($this->_width)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Problem reading tiff file. Tiff is probably corrupt.');
        }
        $this->_image_properties = [];
        $this->_image_properties['bitDepth'] = $this->_bits_per_sample;
        $this->_image_properties['fileSize'] = $this->_file_size;
        $this->_image_properties['TIFFendianType'] = $this->_endian_type;
        $this->_image_properties['TIFFcompressionType'] = $this->_compression;
        $this->_image_properties['TIFFwhiteIsZero'] = $this->_white_is_zero;
        $this->_image_properties['TIFFblackIsZero'] = $this->_black_is_zero;
        $this->_image_properties['TIFFcolorCode'] = $this->_color_code;
        $this->_image_properties['TIFFimageDataOffset'] = $this->_image_data_offset;
        $this->_image_properties['TIFFimageDataLength'] = $this->_image_data_length;
        $this->_image_properties['PDFfilter'] = $this->_filter;
        $this->_image_properties['PDFcolorSpace'] = $this->_color_space;
        $image_dictionary->Width = new Zend_Pdf_Element_Numeric($this->_width);
        if ($this->_white_is_zero === true) {
            $image_dictionary->Decode = new Zend_Pdf_Element_Array([new Zend_Pdf_Element_Numeric(1), new Zend_Pdf_Element_Numeric(0)]);
        }
        $image_dictionary->Height = new Zend_Pdf_Element_Numeric($this->_height);
        $image_dictionary->color_space = new Zend_Pdf_Element_Name($this->_color_space);
        $image_dictionary->bits_per_component = new Zend_Pdf_Element_Numeric($this->_bits_per_sample);
        if (isset($this->_filter) && $this->_filter != 'None') {
            $image_dictionary->Filter = new Zend_Pdf_Element_Name($this->_filter);
        }
        $this->_resource->value = $image_data_bytes;
        $this->_resource->skip_filters();
    }
    /**
     * Image width (defined in Zend_Pdf_Resource_Image_Interface)
     */
    public function get_pixel_width()
    {
        return $this->_width;
    }
    /**
     * Image height (defined in Zend_Pdf_Resource_Image_Interface)
     */
    public function get_pixel_height()
    {
        return $this->_height;
    }
    /**
     * Image properties (defined in Zend_Pdf_Resource_Image_Interface)
     */
    public function get_properties()
    {
        return $this->_image_properties;
    }
}