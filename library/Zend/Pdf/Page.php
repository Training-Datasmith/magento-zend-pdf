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
#require_once 'Zend/Pdf/Element.php';
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/String/Binary.php';
#require_once 'Zend/Pdf/Element/Boolean.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Null.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
#require_once 'Zend/Pdf/Element/String.php';
#require_once 'Zend/Pdf/Resource/Unified.php';
#require_once 'Zend/Pdf/Canvas/Abstract.php';
/**
 * PDF Page
 *
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Page extends Zend_Pdf_Canvas_Abstract
{
    /**** Class Constants ****/
    /* Page Sizes */
    /**
     * Size representing an A4 page in portrait (tall) orientation.
     */
    public const SIZE_A4 = '595:842:';
    /**
     * Size representing an A4 page in landscape (wide) orientation.
     */
    public const SIZE_A4_LANDSCAPE = '842:595:';
    /**
     * Size representing a US Letter page in portrait (tall) orientation.
     */
    public const SIZE_LETTER = '612:792:';
    /**
     * Size representing a US Letter page in landscape (wide) orientation.
     */
    public const SIZE_LETTER_LANDSCAPE = '792:612:';
    /* Shape Drawing */
    /**
     * Stroke the path only. Do not fill.
     */
    public const SHAPE_DRAW_STROKE = 0;
    /**
     * Fill the path only. Do not stroke.
     */
    public const SHAPE_DRAW_FILL = 1;
    /**
     * Fill and stroke the path.
     */
    public const SHAPE_DRAW_FILL_AND_STROKE = 2;
    /* Shape Filling Methods */
    /**
     * Fill the path using the non-zero winding rule.
     */
    public const FILL_METHOD_NON_ZERO_WINDING = 0;
    /**
     * Fill the path using the even-odd rule.
     */
    public const FILL_METHOD_EVEN_ODD = 1;
    /* Line Dash Types */
    /**
     * Solid line dash.
     */
    public const LINE_DASHING_SOLID = 0;
    /**
     * PDF objects factory.
     *
     * @var Zend_Pdf_ElementFactory_Interface
     */
    protected $_obj_factory;
    /**
     * Flag which signals, that page is created separately from any PDF document or
     * attached to anyone.
     *
     * @var boolean
     */
    protected $_attached;
    /**
     * Safe Graphics State semafore
     *
     * If it's false, than we can't be sure Graphics State is restored withing
     * context of previous contents stream (ex. drawing coordinate system may be rotated).
     * We should encompass existing content with save/restore GS operators
     *
     * @var boolean
     */
    protected $_safe_gs;
    /**
     * Object constructor.
     * Constructor signatures:
     *
     * 1. Load PDF page from a parsed PDF file.
     *    Object factory is created by PDF parser.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(Zend_Pdf_Element_Dictionary       $pageDict,
     *                   Zend_Pdf_ElementFactory_Interface $factory);
     * ---------------------------------------------------------
     *
     * 2. Make a copy of the PDF page.
     *    New page is created in the same context as source page. Object factory is shared.
     *    Thus it will be attached to the document, but need to be placed into Zend_Pdf::$pages array
     *    to be included into output.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(Zend_Pdf_Page $page);
     * ---------------------------------------------------------
     *
     * 3. Create new page with a specified pagesize.
     *    If $factory is null then it will be created and page must be attached to the document to be
     *    included into output.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(string $pagesize, Zend_Pdf_ElementFactory_Interface $factory = null);
     * ---------------------------------------------------------
     *
     * 4. Create new page with a specified pagesize (in default user space units).
     *    If $factory is null then it will be created and page must be attached to the document to be
     *    included into output.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(numeric $width, numeric $height, Zend_Pdf_ElementFactory_Interface $factory = null);
     * ---------------------------------------------------------
     *
     *
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     * @throws Zend_Pdf_Exception
     */
    public function __construct($param1, $param2 = null, $param3 = null)
    {
        if (($param1 instanceof Zend_Pdf_Element_Reference || $param1 instanceof Zend_Pdf_Element_Object) && $param2 instanceof Zend_pdf_element_Factory_interface && $param3 === null) {
            switch ($param1->get_type()) {
                case Zend_Pdf_Element::TYPE_DICTIONARY:
                    $this->_dictionary = $param1;
                    $this->_obj_factory = $param2;
                    $this->_attached = true;
                    $this->_safe_gs = false;
                    return;
                case Zend_Pdf_Element::TYPE_NULL:
                    $this->_obj_factory = $param2;
                    $page_width = $page_height = 0;
                    break;
                default:
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Unrecognized object type.');
            }
        } else {
            if ($param1 instanceof Zend_Pdf_Page && $param2 === null && $param3 === null) {
                // Duplicate existing page.
                // Let already existing content and resources to be shared between pages
                // We don't give existing content modification functionality, so we don't need "deep copy"
                $this->_obj_factory = $param1->_obj_factory;
                $this->_attached =& $param1->_attached;
                $this->_safe_gs = false;
                $this->_dictionary = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
                foreach ($param1->_dictionary->get_keys() as $key) {
                    if ($key == 'Contents') {
                        // Clone Contents property
                        $this->_dictionary->Contents = new Zend_Pdf_Element_Array();
                        if ($param1->_dictionary->Contents->get_type() != Zend_Pdf_Element::TYPE_ARRAY) {
                            // Prepare array of content streams and add existing stream
                            $this->_dictionary->Contents->items[] = $param1->_dictionary->Contents;
                        } else {
                            // Clone array of the content streams
                            foreach ($param1->_dictionary->Contents->items as $src_content_stream) {
                                $this->_dictionary->Contents->items[] = $src_content_stream;
                            }
                        }
                    } else {
                        $this->_dictionary->{$key} = $param1->_dictionary->{$key};
                    }
                }
                return;
            }
            if (is_string($param1) && ($param2 === null || $param2 instanceof Zend_pdf_element_Factory_interface) && $param3 === null) {
                if ($param2 !== null) {
                    $this->_obj_factory = $param2;
                } else {
                    #require_once 'Zend/Pdf/ElementFactory.php';
                    $this->_obj_factory = Zend_pdf_element_Factory::create_factory(1);
                }
                $this->_attached = false;
                $this->_safe_gs = true;
                /** New page created. That's users App responsibility to track GS changes */
                switch (strtolower($param1)) {
                    case 'a4':
                        $param1 = Zend_Pdf_Page::SIZE_A4;
                        break;
                    case 'a4-landscape':
                        $param1 = Zend_Pdf_Page::SIZE_A4_LANDSCAPE;
                        break;
                    case 'letter':
                        $param1 = Zend_Pdf_Page::SIZE_LETTER;
                        break;
                    case 'letter-landscape':
                        $param1 = Zend_Pdf_Page::SIZE_LETTER_LANDSCAPE;
                        break;
                    default:
                }
                $page_dim = explode(':', $param1);
                if (count($page_dim) == 2 || count($page_dim) == 3) {
                    $page_width = $page_dim[0];
                    $page_height = $page_dim[1];
                } else {
                    /**
                     * @todo support of user defined pagesize notations, like:
                     *       "210x297mm", "595x842", "8.5x11in", "612x792"
                     */
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Wrong pagesize notation.');
                }
                /**
                 * @todo support of pagesize recalculation to "default user space units"
                 */
            } elseif (is_numeric($param1) && is_numeric($param2) && ($param3 === null || $param3 instanceof Zend_pdf_element_Factory_interface)) {
                if ($param3 !== null) {
                    $this->_obj_factory = $param3;
                } else {
                    #require_once 'Zend/Pdf/ElementFactory.php';
                    $this->_obj_factory = Zend_pdf_element_Factory::create_factory(1);
                }
                $this->_attached = false;
                $this->_safe_gs = true;
                /** New page created. That's users App responsibility to track GS changes */
                $page_width = $param1;
                $page_height = $param2;
            } else {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Unrecognized method signature, wrong number of arguments or wrong argument types.');
            }
        }
        $this->_dictionary = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
        $this->_dictionary->Type = new Zend_Pdf_Element_Name('Page');
        #require_once 'Zend/Pdf.php';
        $this->_dictionary->last_modified = new Zend_Pdf_Element_String(Zend_Pdf::pdf_date());
        $this->_dictionary->Resources = new Zend_Pdf_Element_Dictionary();
        $this->_dictionary->media_box = new Zend_Pdf_Element_Array();
        $this->_dictionary->media_box->items[] = new Zend_Pdf_Element_Numeric(0);
        $this->_dictionary->media_box->items[] = new Zend_Pdf_Element_Numeric(0);
        $this->_dictionary->media_box->items[] = new Zend_Pdf_Element_Numeric($page_width);
        $this->_dictionary->media_box->items[] = new Zend_Pdf_Element_Numeric($page_height);
        $this->_dictionary->Contents = new Zend_Pdf_Element_Array();
    }
    /**
     * Attach resource to the canvas
     *
     * Method returns a name of the resource which can be used
     * as a resource reference within drawing instructions stream
     * Allowed types: 'ExtGState', 'ColorSpace', 'Pattern', 'Shading',
     * 'XObject', 'Font', 'Properties'
     *
     * @param string $type
     * @return string
     */
    protected function _attach_resource($type, Zend_Pdf_Resource $resource)
    {
        // Check that Resources dictionary contains appropriate resource set
        if ($this->_dictionary->Resources->{$type} === null) {
            $this->_dictionary->Resources->touch();
            $this->_dictionary->Resources->{$type} = new Zend_Pdf_Element_Dictionary();
        } else {
            $this->_dictionary->Resources->{$type}->touch();
        }
        // Check, that resource is already attached to resource set.
        $res_object = $resource->get_resource();
        foreach ($this->_dictionary->Resources->{$type}->get_keys() as $res_id) {
            if ($this->_dictionary->Resources->{$type}->{$res_id} === $res_object) {
                return $res_id;
            }
        }
        $id_counter = 1;
        do {
            $new_res_name = $type[0] . $id_counter++;
        } while ($this->_dictionary->Resources->{$type}->{$new_res_name} !== null);
        $this->_dictionary->Resources->{$type}->{$new_res_name} = $res_object;
        $this->_obj_factory->attach($resource->get_factory());
        return $new_res_name;
    }
    /**
     * Add procedureSet to the Page description
     *
     * @param string $procSetName
     */
    protected function _add_proc_set($proc_set_name)
    {
        // Check that Resources dictionary contains ProcSet entry
        if ($this->_dictionary->Resources->proc_set === null) {
            $this->_dictionary->Resources->touch();
            $this->_dictionary->Resources->proc_set = new Zend_Pdf_Element_Array();
        } else {
            $this->_dictionary->Resources->proc_set->touch();
        }
        foreach ($this->_dictionary->Resources->proc_set->items as $proc_set_entry) {
            if ($proc_set_entry->value == $proc_set_name) {
                // Procset is already included into a ProcSet array
                return;
            }
        }
        $this->_dictionary->Resources->proc_set->items[] = new Zend_Pdf_Element_Name($proc_set_name);
    }
    /**
     * Returns dictionaries of used resources.
     *
     * Used for canvas implementations interoperability
     *
     * Structure of the returned array:
     * array(
     *   <resTypeName> => array(
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      ...
     *                    ),
     *   <resTypeName> => array(
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      <resName> => <Zend_Pdf_Resource object>,
     *                      ...
     *                    ),
     *   ...
     *   'ProcSet' => array()
     * )
     *
     * where ProcSet array is a list of used procedure sets names (strings).
     * Allowed procedure set names: 'PDF', 'Text', 'ImageB', 'ImageC', 'ImageI'
     *
     * @internal
     */
    public function get_resources(): array
    {
        $resources = [];
        $res_dictionary = $this->_dictionary->Resources;
        foreach ($res_dictionary->get_keys() as $res_type) {
            $resources[$res_type] = [];
            if ($res_type == 'ProcSet') {
                foreach ($res_dictionary->proc_set->items as $proc_set_entry) {
                    $resources[$res_type][] = $proc_set_entry->value;
                }
            } else {
                $res_map = $res_dictionary->{$res_type};
                foreach ($res_map->get_keys() as $res_id) {
                    $resources[$res_type][$res_id] = new Zend_Pdf_Resource_Unified($res_map->{$res_id});
                }
            }
        }
        return $resources;
    }
    /**
     * Get drawing instructions stream
     *
     * It has to be returned as a PDF stream object to make it reusable.
     *
     * @internal
     * @returns Zend_Pdf_Resource_ContentStream
     */
    public function get_contents()
    {
        /** @todo implementation */
    }
    /**
     * Return the height of this page in points.
     *
     * @return float
     */
    public function get_height()
    {
        return $this->_dictionary->media_box->items[3]->value - $this->_dictionary->media_box->items[1]->value;
    }
    /**
     * Return the width of this page in points.
     *
     * @return float
     */
    public function get_width()
    {
        return $this->_dictionary->media_box->items[2]->value - $this->_dictionary->media_box->items[0]->value;
    }
    /**
     * Clone page, extract it and dependent objects from the current document,
     * so it can be used within other docs.
     */
    public function __clone()
    {
        $factory = Zend_pdf_element_Factory::create_factory(1);
        $processed = [];
        // Clone dictionary object.
        // Do it explicitly to prevent sharing page attributes between different
        // results of clonePage() operation (other resources are still shared)
        $dictionary = new Zend_Pdf_Element_Dictionary();
        foreach ($this->_dictionary->get_keys() as $key) {
            $dictionary->{$key} = $this->_dictionary->{$key}->make_clone($factory->get_factory(), $processed, Zend_Pdf_Element::CLONE_MODE_SKIP_PAGES);
        }
        $this->_dictionary = $factory->new_object($dictionary);
        $this->_obj_factory = $factory;
        $this->_attached = false;
        $this->_style = null;
        $this->_font = null;
    }
    /**
     * Clone page, extract it and dependent objects from the current document,
     * so it can be used within other docs.
     *
     * @internal
     * @param Zend_Pdf_ElementFactory_Interface $factory
     * @param array $processed
     */
    public function clone_page($factory, &$processed): \Zend_Pdf_Page
    {
        // Clone dictionary object.
        // Do it explicitly to prevent sharing page attributes between different
        // results of clonePage() operation (other resources are still shared)
        $dictionary = new Zend_Pdf_Element_Dictionary();
        foreach ($this->_dictionary->get_keys() as $key) {
            $dictionary->{$key} = $this->_dictionary->{$key}->make_clone($factory->get_factory(), $processed, Zend_Pdf_Element::CLONE_MODE_SKIP_PAGES);
        }
        $cloned_page = new Zend_Pdf_Page($factory->new_object($dictionary), $factory);
        $cloned_page->_attached = false;
        return $cloned_page;
    }
    /**
     * Retrive PDF file reference to the page
     *
     * @internal
     * @return Zend_Pdf_Element_Dictionary
     */
    public function get_page_dictionary()
    {
        return $this->_dictionary;
    }
    /**
     * Dump current drawing instructions into the content stream.
     *
     * @todo Don't forget to close all current graphics operations (like path drawing)
     *
     * @throws Zend_Pdf_Exception
     */
    public function flush()
    {
        if ($this->_save_count != 0) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Saved graphics state is not restored');
        }
        if ($this->_contents == '') {
            return;
        }
        if ($this->_dictionary->Contents->get_type() != Zend_Pdf_Element::TYPE_ARRAY) {
            /**
             * It's a stream object.
             * Prepare Contents page attribute for update.
             */
            $this->_dictionary->touch();
            $current_page_contents = $this->_dictionary->Contents;
            $this->_dictionary->Contents = new Zend_Pdf_Element_Array();
            $this->_dictionary->Contents->items[] = $current_page_contents;
        } else {
            $this->_dictionary->Contents->touch();
        }
        if (!$this->_safe_gs && count($this->_dictionary->Contents->items) != 0) {
            /**
             * Page already has some content which is not treated as safe.
             *
             * Add save/restore GS operators
             */
            $this->_add_proc_set('PDF');
            $new_contents_array = new Zend_Pdf_Element_Array();
            $new_contents_array->items[] = $this->_obj_factory->new_stream_object(" q\n");
            foreach ($this->_dictionary->Contents->items as $content_stream) {
                $new_contents_array->items[] = $content_stream;
            }
            $new_contents_array->items[] = $this->_obj_factory->new_stream_object(" Q\n");
            $this->_dictionary->touch();
            $this->_dictionary->Contents = $new_contents_array;
            $this->_safe_gs = true;
        }
        $this->_dictionary->Contents->items[] = $this->_obj_factory->new_stream_object($this->_contents);
        $this->_contents = '';
    }
    /**
     * Prepare page to be rendered into PDF.
     *
     * @todo Don't forget to close all current graphics operations (like path drawing)
     *
     * @throws Zend_Pdf_Exception
     */
    public function render(Zend_pdf_element_Factory_interface $obj_factory)
    {
        $this->flush();
        if ($obj_factory === $this->_obj_factory) {
            // Page is already attached to the document.
            return;
        }
        if ($this->_attached) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Page is attached to other documen. Use clone $page to get it context free.');
        }
        $obj_factory->attach($this->_obj_factory);
    }
    /**
     * Extract resources attached to the page
     *
     * This method is not intended to be used in userland, but helps to optimize some document wide operations
     *
     * returns array of Zend_Pdf_Element_Dictionary objects
     *
     * @internal
     * @return array
     */
    public function extract_resources()
    {
        return $this->_dictionary->Resources;
    }
    /**
     * Extract fonts attached to the page
     *
     * returns array of Zend_Pdf_Resource_Font_Extracted objects
     *
     * @throws Zend_Pdf_Exception
     */
    public function extract_fonts(): array
    {
        if ($this->_dictionary->Resources->Font === null) {
            // Page doesn't have any font attached
            // Return empty array
            return [];
        }
        $font_resources = $this->_dictionary->Resources->Font;
        $font_resources_unique = [];
        foreach ($font_resources->get_keys() as $font_resource_name) {
            $font_dictionary = $font_resources->{$font_resource_name};
            if (!($font_dictionary instanceof Zend_Pdf_Element_Reference || $font_dictionary instanceof Zend_Pdf_Element_Object)) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Font dictionary has to be an indirect object or object reference.');
            }
            $font_resources_unique[spl_object_hash($font_dictionary->get_object())] = $font_dictionary;
        }
        $fonts = [];
        #require_once 'Zend/Pdf/Exception.php';
        foreach ($font_resources_unique as $resource_id => $font_dictionary) {
            try {
                #require_once 'Zend/Pdf/Resource/Font/Extracted.php';
                // Try to extract font
                $extracted_font = new Zend_Pdf_Resource_Font_Extracted($font_dictionary);
                $fonts[$resource_id] = $extracted_font;
            } catch (Zend_Pdf_Exception $e) {
                if ($e->get_message() != 'Unsupported font type.') {
                    throw new Zend_Pdf_Exception($e->get_message(), $e->get_code(), $e);
                }
            }
        }
        return $fonts;
    }
    /**
     * Extract font attached to the page by specific font name
     *
     * $fontName should be specified in UTF-8 encoding
     *
     * @return Zend_Pdf_Resource_Font_Extracted|null
     * @throws Zend_Pdf_Exception
     */
    public function extract_font($font_name)
    {
        if ($this->_dictionary->Resources->Font === null) {
            // Page doesn't have any font attached
            return null;
        }
        $font_resources = $this->_dictionary->Resources->Font;
        $font_resources_unique = [];
        #require_once 'Zend/Pdf/Exception.php';
        foreach ($font_resources->get_keys() as $font_resource_name) {
            $font_dictionary = $font_resources->{$font_resource_name};
            if (!($font_dictionary instanceof Zend_Pdf_Element_Reference || $font_dictionary instanceof Zend_Pdf_Element_Object)) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Font dictionary has to be an indirect object or object reference.');
            }
            $resource_id = spl_object_hash($font_dictionary->get_object());
            if (isset($font_resources_unique[$resource_id])) {
                continue;
            }
            // Mark resource as processed
            $font_resources_unique[$resource_id] = 1;
            if ($font_dictionary->base_font->value != $font_name) {
                continue;
            }
            try {
                // Try to extract font
                #require_once 'Zend/Pdf/Resource/Font/Extracted.php';
                return new Zend_Pdf_Resource_Font_Extracted($font_dictionary);
            } catch (Zend_Pdf_Exception $e) {
                if ($e->get_message() != 'Unsupported font type.') {
                    throw new Zend_Pdf_Exception($e->get_message(), $e->get_code(), $e);
                }
                // Continue searhing font with specified name
            }
        }
        return null;
    }
    public function attach_annotation(Zend_Pdf_Annotation $annotation): self
    {
        $annotation_dictionary = $annotation->get_resource();
        if (!$annotation_dictionary instanceof Zend_Pdf_Element_Object && !$annotation_dictionary instanceof Zend_Pdf_Element_Reference) {
            $annotation_dictionary = $this->_obj_factory->new_object($annotation_dictionary);
        }
        if ($this->_dictionary->Annots === null) {
            $this->_dictionary->touch();
            $this->_dictionary->Annots = new Zend_Pdf_Element_Array();
        } else {
            $this->_dictionary->Annots->touch();
        }
        $this->_dictionary->Annots->items[] = $annotation_dictionary;
        $annotation_dictionary->touch();
        $annotation_dictionary->P = $this->_dictionary;
        return $this;
    }
}