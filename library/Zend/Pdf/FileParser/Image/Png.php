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
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** @see Zend_Pdf_FileParser_Image */
#require_once 'Zend/Pdf/FileParser/Image.php';
/**
 * Abstract base class for Image file parsers.
 *
 * @package    Zend_Pdf
 * @subpackage FileParser
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_pdf_file_Parser_image_png extends Zend_pdf_file_Parser_image
{
    protected $_is_png;
    protected $_width;
    protected $_height;
    protected $_bits;
    protected $_color;
    protected $_compression;
    protected $_pre_filter;
    protected $_interlacing;
    protected $_image_data;
    protected $_palette_data;
    protected $_transparency_data;
    /**** Public Interface ****/
    public function get_width()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_width;
    }
    public function get_height()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_width;
    }
    public function get_bit_depth()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_bits;
    }
    public function get_color_space()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_color;
    }
    public function get_compression_strategy()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_compression;
    }
    public function get_paeth_filter()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_pre_filter;
    }
    public function get_interlacing_mode()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_interlacing;
    }
    public function get_raw_image_data()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_image_data;
    }
    public function get_raw_palette_data()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_palette_data;
    }
    public function get_raw_transparency_data()
    {
        if (!$this->_is_parsed) {
            $this->parse();
        }
        return $this->_transparency_data;
    }
    /* Semi-Concrete Class Implementation */
    /**
     * Verifies that the image file is in the expected format.
     *
     * @throws Zend_Pdf_Exception
     */
    public function screen()
    {
        if ($this->_is_screened) {
            return;
        }
        return $this->_check_signature();
    }
    /**
     * Reads and parses the image data from the file on disk.
     *
     * @throws Zend_Pdf_Exception
     */
    public function parse()
    {
        if ($this->_is_parsed) {
            return;
        }
        /* Screen the font file first, if it hasn't been done yet.
         */
        $this->screen();
        $this->_parse_ihdr_chunk();
        $this->_parse_chunks();
    }
    protected function _parse_signature()
    {
        $this->move_to_offset(1);
        //Skip the first byte (%)
        if ('PNG' != $this->read_bytes(3)) {
            $this->_is_png = false;
        } else {
            $this->_is_png = true;
        }
    }
    protected function _check_signature()
    {
        if (!isset($this->_is_png)) {
            $this->_parse_signature();
        }
        return $this->_is_png;
    }
    protected function _parse_chunks()
    {
        $this->move_to_offset(33);
        //Variable chunks start at the end of IHDR
        //Start processing chunks. If there are no more bytes to read parsing is complete.
        $size = $this->get_size();
        while ($size - $this->get_offset() >= 8) {
            $chunk_length = $this->read_u_int(4);
            if ($chunk_length < 0 || $chunk_length + $this->get_offset() + 4 > $size) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('PNG Corrupt: Invalid Chunk Size In File.');
            }
            $chunk_type = $this->read_bytes(4);
            $offset = $this->get_offset();
            //If we know how to process the chunk, do it here, else ignore the chunk and move on to the next
            switch ($chunk_type) {
                case 'IDAT':
                    // This chunk may appear more than once. It contains the actual image data.
                    $this->_parse_idat_chunk($offset, $chunk_length);
                    break;
                case 'PLTE':
                    // This chunk contains the image palette.
                    $this->_parse_plte_chunk($offset, $chunk_length);
                    break;
                case 'tRNS':
                    // This chunk contains non-alpha channel transparency data
                    $this->_parse_trns_chunk($offset, $chunk_length);
                    break;
                case 'IEND':
                    break 2;
            }
            if ($offset + $chunk_length + 4 < $size) {
                $this->move_to_offset($offset + $chunk_length + 4);
                //Skip past the data finalizer. (Don't rely on the parse to leave the offsets correct)
            }
        }
        if (empty($this->_image_data)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('This PNG is corrupt. All png must contain IDAT chunks.');
        }
    }
    protected function _parse_ihdr_chunk()
    {
        $this->move_to_offset(12);
        //IHDR must always start at offset 12 and run for 17 bytes
        if (!$this->read_bytes(4) == 'IHDR') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('This PNG is corrupt. The first chunk in a PNG file must be IHDR.');
        }
        $this->_width = $this->read_u_int(4);
        $this->_height = $this->read_u_int(4);
        $this->_bits = $this->read_int(1);
        $this->_color = $this->read_int(1);
        $this->_compression = $this->read_int(1);
        $this->_pre_filter = $this->read_int(1);
        $this->_interlacing = $this->read_int(1);
        if ($this->_interlacing != Zend_Pdf_Image::PNG_INTERLACING_DISABLED) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Only non-interlaced images are currently supported.');
        }
    }
    protected function _parse_idat_chunk($chunk_offset, $chunk_length)
    {
        $this->move_to_offset($chunk_offset);
        if (!isset($this->_image_data)) {
            $this->_image_data = $this->read_bytes($chunk_length);
        } else {
            $this->_image_data .= $this->read_bytes($chunk_length);
        }
    }
    protected function _parse_plte_chunk($chunk_offset, $chunk_length)
    {
        $this->move_to_offset($chunk_offset);
        $this->_palette_data = $this->read_bytes($chunk_length);
    }
    protected function _parse_trns_chunk($chunk_offset, $chunk_length)
    {
        $this->move_to_offset($chunk_offset);
        //Processing of tRNS data varies dependending on the color depth
        switch ($this->_color) {
            case Zend_Pdf_Image::PNG_CHANNEL_GRAY:
                $base_color = $this->read_int(1);
                $this->_transparency_data = [$base_color, $base_color];
                break;
            case Zend_Pdf_Image::PNG_CHANNEL_RGB:
                //@TODO Fix this hack.
                //This parser cheats and only uses the lsb's (and only works with < 16 bit depth images)
                /*
                                     From the standard:
                     For color type 2 (truecolor), the tRNS chunk contains a single RGB color value, stored in the format:
                     Red:   2 bytes, range 0 .. (2^bitdepth)-1
                                     Green: 2 bytes, range 0 .. (2^bitdepth)-1
                                     Blue:  2 bytes, range 0 .. (2^bitdepth)-1
                     (If the image bit depth is less than 16, the least significant bits are used and the others are 0.)
                                     Pixels of the specified color value are to be treated as transparent (equivalent to alpha value 0);
                                     all other pixels are to be treated as fully opaque (alpha value 2bitdepth-1).
                */
                $red = $this->read_int(1);
                $this->skip_bytes(1);
                $green = $this->read_int(1);
                $this->skip_bytes(1);
                $blue = $this->read_int(1);
                $this->_transparency_data = [$red, $red, $green, $green, $blue, $blue];
                break;
            case Zend_Pdf_Image::PNG_CHANNEL_INDEXED:
                //@TODO Fix this hack.
                //This parser cheats too. It only masks the first color in the palette.
                /*
                                     From the standard:
                     For color type 3 (indexed color), the tRNS chunk contains a series of one-byte alpha values, corresponding to entries in the PLTE chunk:
                        Alpha for palette index 0:  1 byte
                                        Alpha for palette index 1:  1 byte
                                        ...etc...
                     Each entry indicates that pixels of the corresponding palette index must be treated as having the specified alpha value.
                                     Alpha values have the same interpretation as in an 8-bit full alpha channel: 0 is fully transparent, 255 is fully opaque,
                                     regardless of image bit depth. The tRNS chunk must not contain more alpha values than there are palette entries,
                                     but tRNS can contain fewer values than there are palette entries. In this case, the alpha value for all remaining palette
                                     entries is assumed to be 255. In the common case in which only palette index 0 need be made transparent, only a one-byte
                                     tRNS chunk is needed.
                */
                $tmp_data = $this->read_bytes($chunk_length);
                if (($trns_idx = strpos($tmp_data, "\x00")) !== false) {
                    $this->_transparency_data = [$trns_idx, $trns_idx];
                }
                break;
            case Zend_Pdf_Image::PNG_CHANNEL_GRAY_ALPHA:
            //Fall through to the next case
            case Zend_Pdf_Image::PNG_CHANNEL_RGB_ALPHA:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('tRNS chunk illegal for Alpha Channel Images');
        }
    }
}