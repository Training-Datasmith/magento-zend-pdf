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
#require_once 'Zend/Pdf/Element/Stream.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Element_Object */
#require_once 'Zend/Pdf/Element/Object.php';
/**
 * PDF file 'stream object' element implementation
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Element_Object_Stream extends Zend_Pdf_Element_Object
{
    /**
     * StreamObject dictionary
     * Required enries:
     * Length
     *
     * @var Zend_Pdf_Element_Dictionary
     */
    private $_dictionary;
    /**
     * Flag which signals, that stream is decoded
     *
     * @var boolean
     */
    private $_stream_decoded;
    /**
     * Stored original stream object dictionary.
     * Used to decode stream at access time.
     *
     * The only properties affecting decoding are sored here.
     *
     * @var array|null
     */
    private $_initial_dictionary_data;
    /**
     * Object constructor
     *
     * @param mixed $val
     * @param integer $objNum
     * @param integer $genNum
     * @param Zend_Pdf_Element_Dictionary|null $dictionary
     * @throws Zend_Pdf_Exception
     */
    public function __construct($val, $obj_num, $gen_num, Zend_pdf_element_Factory $factory, $dictionary = null)
    {
        parent::__construct(new Zend_Pdf_Element_Stream($val), $obj_num, $gen_num, $factory);
        if ($dictionary === null) {
            $this->_dictionary = new Zend_Pdf_Element_Dictionary();
            $this->_dictionary->Length = new Zend_Pdf_Element_Numeric(strlen($val));
            $this->_stream_decoded = true;
        } else {
            $this->_dictionary = $dictionary;
            $this->_stream_decoded = false;
        }
    }
    /**
     * Extract dictionary data which are used to store information and to normalize filters
     * information before defiltering.
     */
    private function _extract_dictionary_data(): array
    {
        $dictionary_array = [];
        $dictionary_array['Filter'] = [];
        $dictionary_array['DecodeParms'] = [];
        if ($this->_dictionary->Filter === null) {
            // Do nothing.
        } elseif ($this->_dictionary->Filter->get_type() == Zend_Pdf_Element::TYPE_ARRAY) {
            foreach ($this->_dictionary->Filter->items as $id => $filter) {
                $dictionary_array['Filter'][$id] = $filter->value;
                $dictionary_array['DecodeParms'][$id] = [];
                if ($this->_dictionary->decode_parms !== null) {
                    if ($this->_dictionary->decode_parms->items[$id] !== null && $this->_dictionary->decode_parms->items[$id]->value !== null) {
                        foreach ($this->_dictionary->decode_parms->items[$id]->get_keys() as $param_key) {
                            $dictionary_array['DecodeParms'][$id][$param_key] = $this->_dictionary->decode_parms->items[$id]->{$param_key}->value;
                        }
                    }
                }
            }
        } elseif ($this->_dictionary->Filter->get_type() != Zend_Pdf_Element::TYPE_NULL) {
            $dictionary_array['Filter'][0] = $this->_dictionary->Filter->value;
            $dictionary_array['DecodeParms'][0] = [];
            if ($this->_dictionary->decode_parms !== null) {
                foreach ($this->_dictionary->decode_parms->get_keys() as $param_key) {
                    $dictionary_array['DecodeParms'][0][$param_key] = $this->_dictionary->decode_parms->{$param_key}->value;
                }
            }
        }
        if ($this->_dictionary->F !== null) {
            $dictionary_array['F'] = $this->_dictionary->F->value;
        }
        $dictionary_array['FFilter'] = [];
        $dictionary_array['FDecodeParms'] = [];
        if ($this->_dictionary->f_filter === null) {
            // Do nothing.
        } elseif ($this->_dictionary->f_filter->get_type() == Zend_Pdf_Element::TYPE_ARRAY) {
            foreach ($this->_dictionary->f_filter->items as $id => $filter) {
                $dictionary_array['FFilter'][$id] = $filter->value;
                $dictionary_array['FDecodeParms'][$id] = [];
                if ($this->_dictionary->f_decode_parms !== null) {
                    if ($this->_dictionary->f_decode_parms->items[$id] !== null && $this->_dictionary->f_decode_parms->items[$id]->value !== null) {
                        foreach ($this->_dictionary->f_decode_parms->items[$id]->get_keys() as $param_key) {
                            $dictionary_array['FDecodeParms'][$id][$param_key] = $this->_dictionary->f_decode_parms->items[$id]->items[$param_key]->value;
                        }
                    }
                }
            }
        } else {
            $dictionary_array['FFilter'][0] = $this->_dictionary->f_filter->value;
            $dictionary_array['FDecodeParms'][0] = [];
            if ($this->_dictionary->f_decode_parms !== null) {
                foreach ($this->_dictionary->f_decode_parms->get_keys() as $param_key) {
                    $dictionary_array['FDecodeParms'][0][$param_key] = $this->_dictionary->f_decode_parms->items[$param_key]->value;
                }
            }
        }
        return $dictionary_array;
    }
    /**
     * Decode stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _decode_stream()
    {
        if ($this->_initial_dictionary_data === null) {
            $this->_initial_dictionary_data = $this->_extract_dictionary_data();
        }
        /**
         * All applied stream filters must be processed to decode stream.
         * If we don't recognize any of applied filetrs an exception should be thrown here
         */
        if (isset($this->_initial_dictionary_data['F'])) {
            /** @todo Check, how external files can be processed. */
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('External filters are not supported now.');
        }
        foreach ($this->_initial_dictionary_data['Filter'] as $id => $filter_name) {
            $value_ref =& $this->_value->value->get_ref();
            $this->_value->value->touch();
            switch ($filter_name) {
                case 'ASCIIHexDecode':
                    #require_once 'Zend/Pdf/Filter/AsciiHex.php';
                    $value_ref = Zend_pdf_filter_ascii_Hex::decode($value_ref);
                    break;
                case 'ASCII85Decode':
                    #require_once 'Zend/Pdf/Filter/Ascii85.php';
                    $value_ref = Zend_Pdf_Filter_Ascii85::decode($value_ref);
                    break;
                case 'FlateDecode':
                    #require_once 'Zend/Pdf/Filter/Compression/Flate.php';
                    $value_ref = Zend_Pdf_Filter_Compression_Flate::decode($value_ref, $this->_initial_dictionary_data['DecodeParms'][$id]);
                    break;
                case 'LZWDecode':
                    #require_once 'Zend/Pdf/Filter/Compression/Lzw.php';
                    $value_ref = Zend_Pdf_Filter_Compression_Lzw::decode($value_ref, $this->_initial_dictionary_data['DecodeParms'][$id]);
                    break;
                case 'RunLengthDecode':
                    #require_once 'Zend/Pdf/Filter/RunLength.php';
                    $value_ref = Zend_pdf_filter_run_Length::decode($value_ref);
                    break;
                default:
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Unknown stream filter: \'' . $filter_name . '\'.');
            }
        }
        $this->_stream_decoded = true;
    }
    /**
     * Encode stream
     *
     * @throws Zend_Pdf_Exception
     */
    private function _encode_stream()
    {
        /**
         * All applied stream filters must be processed to encode stream.
         * If we don't recognize any of applied filetrs an exception should be thrown here
         */
        if (isset($this->_initial_dictionary_data['F'])) {
            /** @todo Check, how external files can be processed. */
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('External filters are not supported now.');
        }
        $filters = array_reverse($this->_initial_dictionary_data['Filter'], true);
        foreach ($filters as $id => $filter_name) {
            $value_ref =& $this->_value->value->get_ref();
            $this->_value->value->touch();
            switch ($filter_name) {
                case 'ASCIIHexDecode':
                    #require_once 'Zend/Pdf/Filter/AsciiHex.php';
                    $value_ref = Zend_pdf_filter_ascii_Hex::encode($value_ref);
                    break;
                case 'ASCII85Decode':
                    #require_once 'Zend/Pdf/Filter/Ascii85.php';
                    $value_ref = Zend_Pdf_Filter_Ascii85::encode($value_ref);
                    break;
                case 'FlateDecode':
                    #require_once 'Zend/Pdf/Filter/Compression/Flate.php';
                    $value_ref = Zend_Pdf_Filter_Compression_Flate::encode($value_ref, $this->_initial_dictionary_data['DecodeParms'][$id]);
                    break;
                case 'LZWDecode':
                    #require_once 'Zend/Pdf/Filter/Compression/Lzw.php';
                    $value_ref = Zend_Pdf_Filter_Compression_Lzw::encode($value_ref, $this->_initial_dictionary_data['DecodeParms'][$id]);
                    break;
                case 'RunLengthDecode':
                    #require_once 'Zend/Pdf/Filter/RunLength.php';
                    $value_ref = Zend_pdf_filter_run_Length::encode($value_ref);
                    break;
                default:
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Unknown stream filter: \'' . $filter_name . '\'.');
            }
        }
        $this->_stream_decoded = false;
    }
    /**
     * Get handler
     *
     * @param string $property
     * @return mixed
     * @throws Zend_Pdf_Exception
     */
    public function __get($property)
    {
        if ($property == 'dictionary') {
            /**
             * If stream is not decoded yet, then store original decoding options (do it only once).
             */
            if (!$this->_stream_decoded && $this->_initial_dictionary_data === null) {
                $this->_initial_dictionary_data = $this->_extract_dictionary_data();
            }
            return $this->_dictionary;
        }
        if ($property == 'value') {
            if (!$this->_stream_decoded) {
                $this->_decode_stream();
            }
            return $this->_value->value->get_ref();
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Unknown stream object property requested.');
    }
    /**
     * Set handler
     *
     * @param string $property
     * @param  mixed $value
     */
    public function __set($property, $value)
    {
        if ($property == 'value') {
            $value_ref =& $this->_value->value->get_ref();
            $value_ref = $value;
            $this->_value->value->touch();
            $this->_stream_decoded = true;
            return;
        }
        #require_once 'Zend/Pdf/Exception.php';
        throw new Zend_Pdf_Exception('Unknown stream object property: \'' . $property . '\'.');
    }
    /**
     * Treat stream data as already encoded
     */
    public function skip_filters()
    {
        $this->_stream_decoded = false;
    }
    /**
     * Call handler
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (!$this->_stream_decoded) {
            $this->_decode_stream();
        }
        switch (count($args)) {
            case 0:
                return $this->_value->{$method}();
            case 1:
                return $this->_value->{$method}($args[0]);
            default:
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Unsupported number of arguments');
        }
    }
    /**
     * Detach PDF object from the factory (if applicable), clone it and attach to new factory.
     *
     * @param Zend_Pdf_ElementFactory $factory  The factory to attach
     * @param array &$processed  List of already processed indirect objects, used to avoid objects duplication
     * @param integer $mode  Cloning mode (defines filter for objects cloning)
     * @returns Zend_Pdf_Element
     */
    public function make_clone(Zend_pdf_element_Factory $factory, array &$processed, $mode)
    {
        $id = spl_object_hash($this);
        if (isset($processed[$id])) {
            // Do nothing if object is already processed
            // return it
            return $processed[$id];
        }
        $this->_dictionary->make_clone($factory, $processed, $mode);
        // Make new empty instance of stream object and register it in $processed container
        $processed[$id] = $cloned_object = $factory->new_stream_object('');
        // Copy current object data and state
        $cloned_object->_dictionary = $this->_dictionary->make_clone($factory, $processed, $mode);
        $cloned_object->_value = $this->_value->make_clone($factory, $processed, $mode);
        $cloned_object->_initial_dictionary_data = $this->_initial_dictionary_data;
        $cloned_object->_stream_decoded = $this->_stream_decoded;
        return $cloned_object;
    }
    /**
     * Dump object to a string to save within PDF file
     *
     * $factory parameter defines operation context.
     */
    public function dump(Zend_pdf_element_Factory $factory): string
    {
        $shift = $factory->get_enumeration_shift($this->_factory);
        if ($this->_stream_decoded) {
            $this->_initial_dictionary_data = $this->_extract_dictionary_data();
            $this->_encode_stream();
        } elseif ($this->_initial_dictionary_data != null) {
            $new_dictionary = $this->_extract_dictionary_data();
            if ($this->_initial_dictionary_data !== $new_dictionary) {
                $this->_decode_stream();
                $this->_initial_dictionary_data = $new_dictionary;
                $this->_encode_stream();
            }
        }
        // Update stream length
        $this->dictionary->Length->value = $this->_value->length();
        return $this->_obj_num + $shift . ' ' . $this->_gen_num . " obj \n" . $this->dictionary->to_string($factory) . "\n" . $this->_value->to_string($factory) . "\n" . "endobj\n";
    }
    /**
     * Clean up resources, used by object
     */
    public function clean_up()
    {
        $this->_dictionary = null;
        $this->_value = null;
    }
}