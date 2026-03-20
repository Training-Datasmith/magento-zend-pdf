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
/** User land classes and interfaces turned on by Zend/Pdf.php file inclusion. */
/** @todo Section should be removed with ZF 2.0 release as obsolete            */
/** Zend_Pdf_Page */
#require_once 'Zend/Pdf/Page.php';
/** Zend_Pdf_Style */
#require_once 'Zend/Pdf/Style.php';
/** Zend_Pdf_Color_GrayScale */
#require_once 'Zend/Pdf/Color/GrayScale.php';
/** Zend_Pdf_Color_Rgb */
#require_once 'Zend/Pdf/Color/Rgb.php';
/** Zend_Pdf_Color_Cmyk */
#require_once 'Zend/Pdf/Color/Cmyk.php';
/** Zend_Pdf_Color_Html */
#require_once 'Zend/Pdf/Color/Html.php';
/** Zend_Pdf_Image */
#require_once 'Zend/Pdf/Image.php';
/** Zend_Pdf_Font */
#require_once 'Zend/Pdf/Font.php';
/** Zend_Pdf_Resource_Extractor */
#require_once 'Zend/Pdf/Resource/Extractor.php';
/** Zend_Pdf_Canvas */
#require_once 'Zend/Pdf/Canvas.php';
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
/**
 * General entity which describes PDF document.
 * It implements document abstraction with a document level operations.
 *
 * Class is used to create new PDF document or load existing document.
 * See details in a class constructor description
 *
 * Class agregates document level properties and entities (pages, bookmarks,
 * document level actions, attachments, form object, etc)
 *
 * @category   Zend
 * @package    Zend_Pdf
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf
{
    /**** Class Constants ****/
    /**
     * Version number of generated PDF documents.
     */
    public const PDF_VERSION = '1.4';
    /**
     * PDF file header.
     */
    public const PDF_HEADER = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
    /**
     * Form field options
     */
    public const PDF_FORM_FIELD_READONLY = 1;
    public const PDF_FORM_FIELD_REQUIRED = 2;
    public const PDF_FORM_FIELD_NOEXPORT = 4;
    /**
     * Pages collection
     *
     * @todo implement it as a class, which supports ArrayAccess and Iterator interfaces,
     *       to provide incremental parsing and pages tree updating.
     *       That will give good performance and memory (PDF size) benefits.
     *
     * @var array   - array of Zend_Pdf_Page object
     */
    public $pages = [];
    /**
     * Document properties
     *
     * It's an associative array with PDF meta information, values may
     * be string, boolean or float.
     * Returned array could be used directly to access, add, modify or remove
     * document properties.
     *
     * Standard document properties: Title (must be set for PDF/X documents), Author,
     * Subject, Keywords (comma separated list), Creator (the name of the application,
     * that created document, if it was converted from other format), Trapped (must be
     * true, false or null, can not be null for PDF/X documents)
     *
     * @var array
     */
    public $properties = [];
    /**
     * Original properties set.
     *
     * Used for tracking properties changes
     *
     * @var array
     */
    protected $_original_properties = [];
    /**
     * Document level javascript
     *
     * @var string
     */
    protected $_java_script;
    /**
     * Document named destinations or "GoTo..." actions, used to refer
     * document parts from outside PDF
     *
     * @var array   - array of Zend_Pdf_Target objects
     */
    protected $_named_targets = [];
    /**
     * Document outlines
     *
     * @var array - array of Zend_Pdf_Outline objects
     */
    public $outlines = [];
    /**
     * Original document outlines list
     * Used to track outlines update
     *
     * @var array - array of Zend_Pdf_Outline objects
     */
    protected $_original_outlines = [];
    /**
     * Original document outlines open elements count
     * Used to track outlines update
     *
     * @var integer
     */
    protected $_original_open_outlines_count = 0;
    /**
     * Pdf trailer (last or just created)
     *
     * @var Zend_Pdf_Trailer
     */
    protected $_trailer;
    /**
     * PDF objects factory.
     *
     * @var Zend_Pdf_ElementFactory_Interface
     */
    protected $_obj_factory;
    /**
     * Memory manager for stream objects
     *
     * @var Zend_Memory_Manager|null
     */
    protected static $_memory_manager;
    /**
     * Pdf file parser.
     * It's not used, but has to be destroyed only with Zend_Pdf object
     *
     * @var Zend_Pdf_Parser
     */
    protected $_parser;
    /**
     * PDF version specified in the file header
     *
     * @var string
     */
    protected $_pdf_header_version;
    /**
     * List of inheritable attributesfor pages tree
     *
     * @var array
     */
    protected static $_inheritable_attributes = ['Resources', 'MediaBox', 'CropBox', 'Rotate'];
    /**
     * List of form fields
     *
     * @var array - Associative array, key: name of form field, value: Zend_Pdf_Element
     */
    protected $_form_fields = [];
    /**
     * True if the object is a newly created PDF document (affects save() method behavior)
     * False otherwise
     *
     * @var boolean
     */
    protected $_is_new_document = true;
    /**
     * Request used memory manager
     *
     * @return Zend_Memory_Manager
     */
    public static function get_memory_manager()
    {
        if (self::$_memory_manager === null) {
            #require_once 'Zend/Memory.php';
            self::$_memory_manager = Zend_Memory::factory('none');
        }
        return self::$_memory_manager;
    }
    /**
     * Set user defined memory manager
     */
    public static function set_memory_manager(Zend_Memory_Manager $memory_manager)
    {
        self::$_memory_manager = $memory_manager;
    }
    /**
     * Create new PDF document from a $source string
     *
     * @param string $source
     * @param integer $revision
     */
    public static function parse(&$source = null, $revision = null): \Zend_Pdf
    {
        return new Zend_Pdf($source, $revision);
    }
    /**
     * Load PDF document from a file
     *
     * @param string $source
     * @param integer $revision
     */
    public static function load($source = null, $revision = null): \Zend_Pdf
    {
        return new Zend_Pdf($source, $revision, true);
    }
    /**
     * Render PDF document and save it.
     *
     * If $updateOnly is true and it's not a new document, then it only
     * appends new section to the end of file.
     *
     * @param string $filename
     * @param boolean $updateOnly
     * @throws Zend_Pdf_Exception
     */
    public function save($filename, $update_only = false)
    {
        if (($file = @fopen($filename, $update_only ? 'ab' : 'wb')) === false) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception("Can not open '{$filename}' file for writing.");
        }
        $this->render($update_only, $file);
        fclose($file);
    }
    /**
     * Creates or loads PDF document.
     *
     * If $source is null, then it creates a new document.
     *
     * If $source is a string and $load is false, then it loads document
     * from a binary string.
     *
     * If $source is a string and $load is true, then it loads document
     * from a file.
     * $revision used to roll back document to specified version
     * (0 - current version, 1 - previous version, 2 - ...)
     *
     * @param string  $source - PDF file to load
     * @param integer $revision
     * @param bool    $load
     * @throws Zend_Pdf_Exception
     * @return Zend_Pdf
     */
    public function __construct($source = null, $revision = null, $load = false)
    {
        #require_once 'Zend/Pdf/ElementFactory.php';
        $this->_obj_factory = Zend_pdf_element_Factory::create_factory(1);
        if ($source !== null) {
            #require_once 'Zend/Pdf/Parser.php';
            $this->_parser = new Zend_Pdf_Parser($source, $this->_obj_factory, $load);
            $this->_pdf_header_version = $this->_parser->get_pdf_version();
            $this->_trailer = $this->_parser->get_trailer();
            if ($this->_trailer->Encrypt !== null) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Encrypted document modification is not supported');
            }
            if ($revision !== null) {
                $this->rollback($revision);
            } else {
                $this->_load_pages($this->_trailer->Root->Pages);
            }
            $this->_load_named_destinations($this->_trailer->Root, $this->_parser->get_pdf_version());
            $this->_load_outlines($this->_trailer->Root);
            $this->_load_java_script($this->_trailer->Root);
            $this->_load_form_fields($this->_trailer->Root);
            if ($this->_trailer->Info !== null) {
                $this->properties = $this->_trailer->Info->to_php();
                if (isset($this->properties['Trapped'])) {
                    switch ($this->properties['Trapped']) {
                        case 'True':
                            $this->properties['Trapped'] = true;
                            break;
                        case 'False':
                            $this->properties['Trapped'] = false;
                            break;
                        case 'Unknown':
                            $this->properties['Trapped'] = null;
                            break;
                        default:
                            // Wrong property value
                            // Do nothing
                            break;
                    }
                }
                $this->_original_properties = $this->properties;
            }
            $this->_is_new_document = false;
        } else {
            $this->_pdf_header_version = Zend_Pdf::PDF_VERSION;
            $trailer_dictionary = new Zend_Pdf_Element_Dictionary();
            /**
             * Document id
             */
            $doc_id = md5(uniqid(random_int(0, mt_getrandmax()), true));
            // 32 byte (128 bit) identifier
            $doc_id_low = substr($doc_id, 0, 16);
            // first 16 bytes
            $doc_id_high = substr($doc_id, 16, 16);
            // second 16 bytes
            $trailer_dictionary->ID = new Zend_Pdf_Element_Array();
            $trailer_dictionary->ID->items[] = new Zend_Pdf_Element_String_Binary($doc_id_low);
            $trailer_dictionary->ID->items[] = new Zend_Pdf_Element_String_Binary($doc_id_high);
            $trailer_dictionary->Size = new Zend_Pdf_Element_Numeric(0);
            #require_once 'Zend/Pdf/Trailer/Generator.php';
            $this->_trailer = new Zend_Pdf_Trailer_Generator($trailer_dictionary);
            /**
             * Document catalog indirect object.
             */
            $doc_catalog = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
            $doc_catalog->Type = new Zend_Pdf_Element_Name('Catalog');
            $doc_catalog->Version = new Zend_Pdf_Element_Name(Zend_Pdf::PDF_VERSION);
            $this->_trailer->Root = $doc_catalog;
            /**
             * Pages container
             */
            $doc_pages = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
            $doc_pages->Type = new Zend_Pdf_Element_Name('Pages');
            $doc_pages->Kids = new Zend_Pdf_Element_Array();
            $doc_pages->Count = new Zend_Pdf_Element_Numeric(0);
            $doc_catalog->Pages = $doc_pages;
        }
    }
    /**
     * Retrive number of revisions.
     */
    public function revisions(): int
    {
        $revisions = 1;
        $current_trailer = $this->_trailer;
        while ($current_trailer->get_prev() !== null && $current_trailer->get_prev()->Root !== null) {
            $revisions++;
            $current_trailer = $current_trailer->get_prev();
        }
        return $revisions++;
    }
    /**
     * Rollback document $steps number of revisions.
     * This method must be invoked before any changes, applied to the document.
     * Otherwise behavior is undefined.
     *
     * @param integer $steps
     */
    public function rollback($steps)
    {
        for ($count = 0; $count < $steps; $count++) {
            if ($this->_trailer->get_prev() !== null && $this->_trailer->get_prev()->Root !== null) {
                $this->_trailer = $this->_trailer->get_prev();
            } else {
                break;
            }
        }
        $this->_obj_factory->set_object_count($this->_trailer->Size->value);
        // Mark content as modified to force new trailer generation at render time
        $this->_trailer->Root->touch();
        $this->pages = [];
        $this->_load_pages($this->_trailer->Root->Pages);
    }
    /**
     * Load pages recursively
     *
     * @param array|null                 $attributes
     * @throws Zend_Pdf_Exception
     */
    protected function _load_pages(Zend_Pdf_Element_Reference $pages, $attributes = [])
    {
        if ($pages->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Wrong argument');
        }
        foreach ($pages->get_keys() as $property) {
            if (in_array($property, self::$_inheritable_attributes)) {
                $attributes[$property] = $pages->{$property};
                $pages->{$property} = null;
            }
        }
        foreach ($pages->Kids->items as $child) {
            if ($child->Type->value == 'Pages') {
                $this->_load_pages($child, $attributes);
            } elseif ($child->Type->value == 'Page') {
                foreach (self::$_inheritable_attributes as $property) {
                    if ($child->{$property} === null && array_key_exists($property, $attributes)) {
                        /**
                         * Important note.
                         * If any attribute or dependant object is an indirect object, then it's still
                         * shared between pages.
                         */
                        if ($attributes[$property] instanceof Zend_Pdf_Element_Object || $attributes[$property] instanceof Zend_Pdf_Element_Reference) {
                            $child->{$property} = $attributes[$property];
                        } else {
                            $child->{$property} = $this->_obj_factory->new_object($attributes[$property]);
                        }
                    }
                }
                #require_once 'Zend/Pdf/Page.php';
                $this->pages[] = new Zend_Pdf_Page($child, $this->_obj_factory);
            }
        }
    }
    /**
     * Load named destinations recursively
     *
     * @param Zend_Pdf_Element_Reference $root Document catalog entry
     * @param string $pdfHeaderVersion
     * @throws Zend_Pdf_Exception
     */
    protected function _load_named_destinations(Zend_Pdf_Element_Reference $root, $pdf_header_version)
    {
        if ($root->Version !== null && version_compare($root->Version->value, $pdf_header_version, '>')) {
            $version_is_1_2_plus = version_compare($root->Version->value, '1.1', '>');
        } else {
            $version_is_1_2_plus = version_compare($pdf_header_version, '1.1', '>');
        }
        if ($version_is_1_2_plus) {
            // PDF version is 1.2+
            // Look for Destinations structure at Name dictionary
            if ($root->Names !== null && $root->Names->Dests !== null) {
                #require_once 'Zend/Pdf/NameTree.php';
                #require_once 'Zend/Pdf/Target.php';
                foreach (new Zend_pdf_name_Tree($root->Names->Dests) as $name => $destination) {
                    $this->_named_targets[$name] = Zend_Pdf_Target::load($destination);
                }
            }
        } else if ($root->Dests !== null) {
            if ($root->Dests->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Document catalog Dests entry must be a dictionary.');
            }
            #require_once 'Zend/Pdf/Target.php';
            foreach ($root->Dests->get_keys() as $dest_key) {
                $this->_named_targets[$dest_key] = Zend_Pdf_Target::load($root->Dests->{$dest_key});
            }
        }
    }
    /**
     * Load outlines recursively
     *
     * @param Zend_Pdf_Element_Reference $root Document catalog entry
     * @throws Zend_Pdf_Exception
     */
    protected function _load_outlines(Zend_Pdf_Element_Reference $root)
    {
        if ($root->Outlines === null) {
            return;
        }
        if ($root->Outlines->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Document catalog Outlines entry must be a dictionary.');
        }
        if ($root->Outlines->Type !== null && $root->Outlines->Type->value != 'Outlines') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Outlines Type entry must be an \'Outlines\' string.');
        }
        if ($root->Outlines->First === null) {
            return;
        }
        $outline_dictionary = $root->Outlines->First;
        $processed_dictionaries = new Spl_Object_Storage();
        while ($outline_dictionary !== null && !$processed_dictionaries->contains($outline_dictionary)) {
            $processed_dictionaries->attach($outline_dictionary);
            #require_once 'Zend/Pdf/Outline/Loaded.php';
            $this->outlines[] = new Zend_Pdf_Outline_Loaded($outline_dictionary);
            $outline_dictionary = $outline_dictionary->Next;
        }
        $this->_original_outlines = $this->outlines;
        if ($root->Outlines->Count !== null) {
            $this->_original_open_outlines_count = $root->Outlines->Count->value;
        }
    }
    /**
     * Load JavaScript
     *
     * Populates the _javaScript string, for later use of getJavaScript method.
     *
     * @param Zend_Pdf_Element_Reference $root Document catalog entry
     */
    protected function _load_java_script(Zend_Pdf_Element_Reference $root)
    {
        if (null === $root->Names || null === $root->Names->java_script || null === $root->Names->java_script->Names) {
            return;
        }
        foreach ($root->Names->java_script->Names->items as $item) {
            if ($item instanceof Zend_Pdf_Element_Reference && $item->S->value === 'JavaScript') {
                $this->_java_script[] = $item->JS->value;
            }
        }
    }
    /**
     * Load form fields
     *
     * Populates the _formFields array, for later lookup of fields by name
     *
     * @param Zend_Pdf_Element_Reference $root Document catalog entry
     */
    protected function _load_form_fields(Zend_Pdf_Element_Reference $root)
    {
        if ($root->acro_form === null || $root->acro_form->Fields === null) {
            return;
        }
        foreach ($root->acro_form->Fields->items as $field) {
            /* We only support fields that are textfields and have a name */
            if ($field->FT && $field->FT->value == 'Tx' && $field->T && $field->T !== null) {
                $this->_form_fields[$field->T->value] = $field;
            }
        }
        if (!$root->acro_form->need_appearances || !$root->acro_form->need_appearances->value) {
            /* Ask the .pdf viewer to generate its own appearance data, so we do not have to */
            $root->acro_form->add(new Zend_Pdf_Element_Name('NeedAppearances'), new Zend_Pdf_Element_Boolean(true));
            $root->acro_form->touch();
        }
    }
    /**
     * Retrieves a list with the names of the AcroForm textfields in the PDF
     *
     * @return array of strings
     */
    public function get_text_field_names(): array
    {
        return array_keys($this->_form_fields);
    }
    /**
     * Sets the value of an AcroForm text field
     *
     * @param string $name Name of textfield
     * @param string $value Value
     * @throws Zend_Pdf_Exception if the textfield does not exist in the pdf
     */
    public function set_text_field($name, $value)
    {
        if (!isset($this->_form_fields[$name])) {
            throw new Zend_Pdf_Exception("Field '{$name}' does not exist or is not a textfield");
        }
        /** @var Zend_Pdf_Element $field */
        $field = $this->_form_fields[$name];
        $field->add(new Zend_Pdf_Element_Name('V'), new Zend_Pdf_Element_String($value));
        $field->touch();
    }
    /**
     * Sets the properties for an AcroForm text field
     *
     * @param string $name
     * @param mixed  $bitmask
     * @throws Zend_Pdf_Exception
     */
    public function set_text_field_properties($name, $bitmask)
    {
        if (!isset($this->_form_fields[$name])) {
            throw new Zend_Pdf_Exception("Field '{$name}' does not exist or is not a textfield");
        }
        $field = $this->_form_fields[$name];
        $field->add(new Zend_Pdf_Element_Name('Ff'), new Zend_Pdf_Element_Numeric($bitmask));
        $field->touch();
    }
    /**
     * Marks an AcroForm text field as read only
     *
     * @param string $name
     */
    public function mark_text_field_as_read_only($name)
    {
        $this->set_text_field_properties($name, self::PDF_FORM_FIELD_READONLY);
    }
    /**
     * Orginize pages to tha pages tree structure.
     *
     * @todo atomatically attach page to the document, if it's not done yet.
     * @todo check, that page is attached to the current document
     *
     * @todo Dump pages as a balanced tree instead of a plain set.
     */
    protected function _dump_pages()
    {
        $root = $this->_trailer->Root;
        $pages_container = $root->Pages;
        $pages_container->touch();
        $pages_container->Kids->items = [];
        foreach ($this->pages as $page) {
            $page->render($this->_obj_factory);
            $page_dictionary = $page->get_page_dictionary();
            $page_dictionary->touch();
            $page_dictionary->Parent = $pages_container;
            $pages_container->Kids->items[] = $page_dictionary;
        }
        $this->_refresh_pages_hash();
        $pages_container->Count->touch();
        $pages_container->Count->value = count($this->pages);
        // Refresh named destinations list
        foreach ($this->_named_targets as $name => $named_target) {
            if ($named_target instanceof Zend_Pdf_Destination_Explicit) {
                // Named target is an explicit destination
                if ($this->resolve_destination($named_target, false) === null) {
                    unset($this->_named_targets[$name]);
                }
            } elseif ($named_target instanceof Zend_Pdf_Action) {
                // Named target is an action
                if ($this->_clean_up_action($named_target, false) === null) {
                    // Action is a GoTo action with an unresolved destination
                    unset($this->_named_targets[$name]);
                }
            } else {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Wrong type of named targed (\'' . get_class($named_target) . '\').');
            }
        }
        // Refresh outlines
        #require_once 'Zend/Pdf/RecursivelyIteratableObjectsContainer.php';
        $iterator = new Recursive_Iterator_Iterator(new Zend_pdf_recursively_Iteratable_Objects_Container($this->outlines), Recursive_Iterator_Iterator::SELF_FIRST);
        foreach ($iterator as $outline) {
            $target = $outline->get_target();
            if ($target !== null) {
                if ($target instanceof Zend_Pdf_Destination) {
                    // Outline target is a destination
                    if ($this->resolve_destination($target, false) === null) {
                        $outline->set_target(null);
                    }
                } elseif ($target instanceof Zend_Pdf_Action) {
                    // Outline target is an action
                    if ($this->_clean_up_action($target, false) === null) {
                        // Action is a GoTo action with an unresolved destination
                        $outline->set_target(null);
                    }
                } else {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Wrong outline target.');
                }
            }
        }
        $open_action = $this->get_open_action();
        if ($open_action !== null) {
            if ($open_action instanceof Zend_Pdf_Action) {
                // OpenAction is an action
                if ($this->_clean_up_action($open_action, false) === null) {
                    // Action is a GoTo action with an unresolved destination
                    $this->set_open_action();
                }
            } elseif ($open_action instanceof Zend_Pdf_Destination) {
                // OpenAction target is a destination
                if ($this->resolve_destination($open_action, false) === null) {
                    $this->set_open_action();
                }
            } else {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('OpenAction has to be either PDF Action or Destination.');
            }
        }
    }
    /**
     * Dump named destinations
     *
     * @todo Create a balanced tree instead of plain structure.
     */
    protected function _dump_named_destinations()
    {
        ksort($this->_named_targets, SORT_STRING);
        $dest_array_items = [];
        foreach ($this->_named_targets as $name => $destination) {
            $dest_array_items[] = new Zend_Pdf_Element_String($name);
            if ($destination instanceof Zend_Pdf_Target) {
                $dest_array_items[] = $destination->get_resource();
            } else {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('PDF named destinations must be a Zend_Pdf_Target object.');
            }
        }
        $dest_array = $this->_obj_factory->new_object(new Zend_Pdf_Element_Array($dest_array_items));
        $dest_tree = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
        $dest_tree->Names = $dest_array;
        $root = $this->_trailer->Root;
        if ($root->Names === null) {
            $root->touch();
            $root->Names = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
        } else {
            $root->Names->touch();
        }
        $root->Names->Dests = $dest_tree;
    }
    /**
     * Dump outlines recursively
     */
    protected function _dump_outlines()
    {
        $root = $this->_trailer->Root;
        if ($root->Outlines === null) {
            if (count($this->outlines) == 0) {
                return;
            }
            $root->Outlines = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
            $root->Outlines->Type = new Zend_Pdf_Element_Name('Outlines');
            $update_outlines_navigation = true;
        } else {
            $update_outlines_navigation = false;
            if (count($this->_original_outlines) != count($this->outlines)) {
                // If original and current outlines arrays have different size then outlines list was updated
                $update_outlines_navigation = true;
            } elseif (!(array_keys($this->_original_outlines) === array_keys($this->outlines))) {
                // If original and current outlines arrays have different keys (with a glance to an order) then outlines list was updated
                $update_outlines_navigation = true;
            } else {
                foreach ($this->outlines as $key => $outline) {
                    if ($this->_original_outlines[$key] !== $outline) {
                        $update_outlines_navigation = true;
                    }
                }
            }
        }
        $last_outline = null;
        $open_outlines_count = 0;
        if ($update_outlines_navigation) {
            $root->Outlines->touch();
            $root->Outlines->First = null;
            foreach ($this->outlines as $outline) {
                if ($last_outline === null) {
                    // First pass. Update Outlines dictionary First entry using corresponding value
                    $last_outline = $outline->dump_outline($this->_obj_factory, $update_outlines_navigation, $root->Outlines);
                    $root->Outlines->First = $last_outline;
                } else {
                    // Update previous outline dictionary Next entry (Prev is updated within dumpOutline() method)
                    $current_outline_dictionary = $outline->dump_outline($this->_obj_factory, $update_outlines_navigation, $root->Outlines, $last_outline);
                    $last_outline->Next = $current_outline_dictionary;
                    $last_outline = $current_outline_dictionary;
                }
                $open_outlines_count += $outline->open_outlines_count();
            }
            $root->Outlines->Last = $last_outline;
        } else {
            foreach ($this->outlines as $outline) {
                $last_outline = $outline->dump_outline($this->_obj_factory, $update_outlines_navigation, $root->Outlines, $last_outline);
                $open_outlines_count += $outline->open_outlines_count();
            }
        }
        if ($open_outlines_count != $this->_original_open_outlines_count) {
            $root->Outlines->touch;
            $root->Outlines->Count = new Zend_Pdf_Element_Numeric($open_outlines_count);
        }
    }
    /**
     * Create page object, attached to the PDF document.
     * Method signatures:
     *
     * 1. Create new page with a specified pagesize.
     *    If $factory is null then it will be created and page must be attached to the document to be
     *    included into output.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(string $pagesize);
     * ---------------------------------------------------------
     *
     * 2. Create new page with a specified pagesize (in default user space units).
     *    If $factory is null then it will be created and page must be attached to the document to be
     *    included into output.
     * ---------------------------------------------------------
     * new Zend_Pdf_Page(numeric $width, numeric $height);
     * ---------------------------------------------------------
     *
     * @param mixed $param1
     * @param mixed $param2
     */
    public function new_page($param1, $param2 = null): \Zend_Pdf_Page
    {
        #require_once 'Zend/Pdf/Page.php';
        if ($param2 === null) {
            return new Zend_Pdf_Page($param1, $this->_obj_factory);
        }
        return new Zend_Pdf_Page($param1, $param2, $this->_obj_factory);
    }
    /**
     * Return the document-level Metadata
     * or null Metadata stream is not presented
     *
     * @return string
     */
    public function get_metadata()
    {
        if ($this->_trailer->Root->Metadata !== null) {
            return $this->_trailer->Root->Metadata->value;
        }
        return null;
    }
    /**
     * Sets the document-level Metadata (mast be valid XMP document)
     *
     * @param string $metadata
     */
    public function set_metadata($metadata)
    {
        $metadata_object = $this->_obj_factory->new_stream_object($metadata);
        $metadata_object->dictionary->Type = new Zend_Pdf_Element_Name('Metadata');
        $metadata_object->dictionary->Subtype = new Zend_Pdf_Element_Name('XML');
        $this->_trailer->Root->Metadata = $metadata_object;
        $this->_trailer->Root->touch();
    }
    /**
     * Return the document-level JavaScript
     * or null if there is no JavaScript for this document
     *
     * @return string
     */
    public function get_java_script()
    {
        return $this->_java_script;
    }
    /**
     * Get open Action
     * Returns Zend_Pdf_Target (Zend_Pdf_Destination or Zend_Pdf_Action object)
     *
     * @return Zend_Pdf_Target
     */
    public function get_open_action()
    {
        if ($this->_trailer->Root->open_action !== null) {
            #require_once 'Zend/Pdf/Target.php';
            return Zend_Pdf_Target::load($this->_trailer->Root->open_action);
        }
        return null;
    }
    /**
     * Set open Action which is actually Zend_Pdf_Destination or Zend_Pdf_Action object
     *
     * @param Zend_Pdf_Target $openAction
     * @returns Zend_Pdf
     */
    public function set_open_action(?Zend_Pdf_Target $open_action = null): self
    {
        $root = $this->_trailer->Root;
        $root->touch();
        if ($open_action === null) {
            $root->open_action = null;
        } else {
            $root->open_action = $open_action->get_resource();
            if ($open_action instanceof Zend_Pdf_Action) {
                $open_action->dump_action($this->_obj_factory);
            }
        }
        return $this;
    }
    /**
     * Return an associative array containing all the named destinations (or GoTo actions) in the PDF.
     * Named targets can be used to reference from outside
     * the PDF, ex: 'http://www.something.com/mydocument.pdf#MyAction'
     *
     * @return array
     */
    public function get_named_destinations()
    {
        return $this->_named_targets;
    }
    /**
     * Return specified named destination
     *
     * @param string $name
     * @return Zend_Pdf_Destination_Explicit|Zend_Pdf_Action_GoTo
     */
    public function get_named_destination($name)
    {
        return $this->_named_targets[$name] ?? null;
    }
    /**
     * Set specified named destination
     *
     * @param string                                             $name
     * @param Zend_Pdf_Destination_Explicit|Zend_Pdf_Action_GoTo $destination
     * @throws Zend_Pdf_Exception
     */
    public function set_named_destination($name, $destination = null)
    {
        if ($destination !== null && !$destination instanceof Zend_pdf_action_go_To && !$destination instanceof Zend_Pdf_Destination_Explicit) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('PDF named destination must refer an explicit destination or a GoTo PDF action.');
        }
        if ($destination !== null) {
            $this->_named_targets[$name] = $destination;
        } else {
            unset($this->_named_targets[$name]);
        }
    }
    /**
     * Pages collection hash:
     * <page dictionary object hash id> => Zend_Pdf_Page
     *
     * @var SplObjectStorage
     */
    protected $_page_references;
    /**
     * Pages collection hash:
     * <page number> => Zend_Pdf_Page
     *
     * @var array
     */
    protected $_page_numbers;
    /**
     * Refresh page collection hashes
     */
    protected function _refresh_pages_hash(): self
    {
        $this->_page_references = [];
        $this->_page_numbers = [];
        $count = 1;
        foreach ($this->pages as $page) {
            $page_dictionary_hash_id = spl_object_hash($page->get_page_dictionary()->get_object());
            $this->_page_references[$page_dictionary_hash_id] = $page;
            $this->_page_numbers[$count++] = $page;
        }
        return $this;
    }
    /**
     * Resolve destination.
     *
     * Returns Zend_Pdf_Page page object or null if destination is not found within PDF document.
     *
     * @param Zend_Pdf_Destination $destination Destination to resolve
     * @param bool $refreshPageCollectionHashes Refresh page collection hashes before processing
     * @return Zend_Pdf_Page|null
     * @throws Zend_Pdf_Exception
     */
    public function resolve_destination(Zend_Pdf_Destination $destination, $refresh_page_collection_hashes = true)
    {
        if ($this->_page_references === null || $refresh_page_collection_hashes) {
            $this->_refresh_pages_hash();
        }
        if ($destination instanceof Zend_Pdf_Destination_Named) {
            if (!isset($this->_named_targets[$destination->get_name()])) {
                return null;
            }
            $destination = $this->get_named_destination($destination->get_name());
            if ($destination instanceof Zend_Pdf_Action) {
                if (!$destination instanceof Zend_pdf_action_go_To) {
                    return null;
                }
                $destination = $destination->get_destination();
            }
            if (!$destination instanceof Zend_Pdf_Destination_Explicit) {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('Named destination target has to be an explicit destination.');
            }
        }
        // Named target is an explicit destination
        $page_element = $destination->get_resource()->items[0];
        if ($page_element->get_type() == Zend_Pdf_Element::TYPE_NUMERIC) {
            // Page reference is a PDF number
            if (!isset($this->_page_numbers[$page_element->value])) {
                return null;
            }
            return $this->_page_numbers[$page_element->value];
        }
        // Page reference is a PDF page dictionary reference
        $page_dictionary_hash_id = spl_object_hash($page_element->get_object());
        if (!isset($this->_page_references[$page_dictionary_hash_id])) {
            return null;
        }
        return $this->_page_references[$page_dictionary_hash_id];
    }
    /**
     * Walk through action and its chained actions tree and remove nodes
     * if they are GoTo actions with an unresolved target.
     *
     * Returns null if root node is deleted or updated action overwise.
     *
     * @todo Give appropriate name and make method public
     *
     * @param bool $refreshPageCollectionHashes Refresh page collection hashes before processing
     * @return Zend_Pdf_Action|null
     */
    protected function _clean_up_action(Zend_Pdf_Action $action, $refresh_page_collection_hashes = true)
    {
        if ($this->_page_references === null || $refresh_page_collection_hashes) {
            $this->_refresh_pages_hash();
        }
        // Named target is an action
        if ($action instanceof Zend_pdf_action_go_To && $this->resolve_destination($action->get_destination(), false) === null) {
            // Action itself is a GoTo action with an unresolved destination
            return null;
        }
        // Walk through child actions
        $iterator = new Recursive_Iterator_Iterator($action, Recursive_Iterator_Iterator::SELF_FIRST);
        $actions_to_clean = [];
        $deletion_candidate_keys = [];
        foreach ($iterator as $chained_action) {
            if ($chained_action instanceof Zend_pdf_action_go_To && $this->resolve_destination($chained_action->get_destination(), false) === null) {
                // Some child action is a GoTo action with an unresolved destination
                // Mark it as a candidate for deletion
                $actions_to_clean[] = $iterator->get_sub_iterator();
                $deletion_candidate_keys[] = $iterator->get_sub_iterator()->key();
            }
        }
        foreach ($actions_to_clean as $id => $action) {
            unset($action->next[$deletion_candidate_keys[$id]]);
        }
        return $action;
    }
    /**
     * Extract fonts attached to the document
     *
     * returns array of Zend_Pdf_Resource_Font_Extracted objects
     *
     * @throws Zend_Pdf_Exception
     */
    public function extract_fonts(): array
    {
        $font_resources_unique = [];
        foreach ($this->pages as $page) {
            $page_resources = $page->extract_resources();
            if ($page_resources->Font === null) {
                // Page doesn't contain have any font reference
                continue;
            }
            $font_resources = $page_resources->Font;
            foreach ($font_resources->get_keys() as $font_resource_name) {
                $font_dictionary = $font_resources->{$font_resource_name};
                if (!($font_dictionary instanceof Zend_Pdf_Element_Reference || $font_dictionary instanceof Zend_Pdf_Element_Object)) {
                    #require_once 'Zend/Pdf/Exception.php';
                    throw new Zend_Pdf_Exception('Font dictionary has to be an indirect object or object reference.');
                }
                $font_resources_unique[spl_object_hash($font_dictionary->get_object())] = $font_dictionary;
            }
        }
        $fonts = [];
        #require_once 'Zend/Pdf/Exception.php';
        foreach ($font_resources_unique as $resource_id => $font_dictionary) {
            try {
                // Try to extract font
                #require_once 'Zend/Pdf/Resource/Font/Extracted.php';
                $extracted_font = new Zend_Pdf_Resource_Font_Extracted($font_dictionary);
                $fonts[$resource_id] = $extracted_font;
            } catch (Zend_Pdf_Exception $e) {
                if ($e->get_message() != 'Unsupported font type.') {
                    throw $e;
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
     * @param string $fontName
     * @return Zend_Pdf_Resource_Font_Extracted|null
     * @throws Zend_Pdf_Exception
     */
    public function extract_font($font_name)
    {
        $font_resources_unique = [];
        #require_once 'Zend/Pdf/Exception.php';
        foreach ($this->pages as $page) {
            $page_resources = $page->extract_resources();
            if ($page_resources->Font === null) {
                // Page doesn't contain have any font reference
                continue;
            }
            $font_resources = $page_resources->Font;
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
                        throw $e;
                    }
                    // Continue searhing
                }
            }
        }
        return null;
    }
    /**
     * Render the completed PDF to a string.
     * If $newSegmentOnly is true and it's not a new document,
     * then only appended part of PDF is returned.
     *
     * @param boolean $newSegmentOnly
     * @param resource $outputStream
     * @return string
     * @throws Zend_Pdf_Exception
     */
    public function render($new_segment_only = false, $output_stream = null)
    {
        if ($this->_is_new_document) {
            // Drop full document first time even $newSegmentOnly is set to true
            $new_segment_only = false;
            $this->_is_new_document = false;
        }
        // Save document properties if necessary
        if ($this->properties != $this->_original_properties) {
            $doc_info = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary());
            foreach ($this->properties as $key => $value) {
                switch ($key) {
                    case 'Trapped':
                        switch ($value) {
                            case true:
                                $doc_info->{$key} = new Zend_Pdf_Element_Name('True');
                                break;
                            case false:
                                $doc_info->{$key} = new Zend_Pdf_Element_Name('False');
                                break;
                            case null:
                                $doc_info->{$key} = new Zend_Pdf_Element_Name('Unknown');
                                break;
                            default:
                                #require_once 'Zend/Pdf/Exception.php';
                                throw new Zend_Pdf_Exception('Wrong Trapped document property vale: \'' . $value . '\'. Only true, false and null values are allowed.');
                        }
                    // no break
                    case 'CreationDate':
                    // break intentionally omitted
                    case 'ModDate':
                        $doc_info->{$key} = new Zend_Pdf_Element_String((string) $value);
                        break;
                    case 'Title':
                    // break intentionally omitted
                    case 'Author':
                    // break intentionally omitted
                    case 'Subject':
                    // break intentionally omitted
                    case 'Keywords':
                    // break intentionally omitted
                    case 'Creator':
                    // break intentionally omitted
                    case 'Producer':
                        if (extension_loaded('mbstring') === true) {
                            $detected = mb_detect_encoding($value);
                            if ($detected !== 'ASCII') {
                                $value = "\xfe\xff" . mb_convert_encoding($value, 'UTF-16', $detected);
                            }
                        }
                        $doc_info->{$key} = new Zend_Pdf_Element_String((string) $value);
                        break;
                    default:
                        // Set property using PDF type based on PHP type
                        $doc_info->{$key} = Zend_Pdf_Element::php_to_pdf($value);
                        break;
                }
            }
            $this->_trailer->Info = $doc_info;
        }
        $this->_dump_pages();
        $this->_dump_named_destinations();
        $this->_dump_outlines();
        // Check, that PDF file was modified
        // File is always modified by _dumpPages() now, but future implementations may eliminate this.
        if (!$this->_obj_factory->is_modified()) {
            if ($new_segment_only) {
                // Do nothing, return
                return '';
            }
            if ($output_stream === null) {
                return $this->_trailer->get_pdf_string();
            }
            $pdf_data = $this->_trailer->get_pdf_string();
            while (strlen($pdf_data) > 0 && ($byte_count = fwrite($output_stream, $pdf_data)) != false) {
                $pdf_data = substr($pdf_data, $byte_count);
            }
            return '';
        }
        // offset (from a start of PDF file) of new PDF file segment
        $offset = $this->_trailer->get_pdf_length();
        // Last Object number in a list of free objects
        $last_free_object = $this->_trailer->get_last_free_object();
        // Array of cross-reference table subsections
        $xref_table = [];
        // Object numbers of first objects in each subsection
        $xref_section_start_nums = [];
        // Last cross-reference table subsection
        $xref_section = [];
        // Dummy initialization of the first element (specail case - header of linked list of free objects).
        $xref_section[] = 0;
        $xref_section_start_nums[] = 0;
        // Object number of last processed PDF object.
        // Used to manage cross-reference subsections.
        // Initialized by zero (specail case - header of linked list of free objects).
        $last_obj_num = 0;
        if ($output_stream !== null) {
            if (!$new_segment_only) {
                $pdf_data = $this->_trailer->get_pdf_string();
                while (strlen($pdf_data) > 0 && ($byte_count = fwrite($output_stream, $pdf_data)) != false) {
                    $pdf_data = substr($pdf_data, $byte_count);
                }
            }
        } else {
            $pdf_segment_blocks = $new_segment_only ? [] : [$this->_trailer->get_pdf_string()];
        }
        // Iterate objects to create new reference table
        foreach ($this->_obj_factory->list_modified_objects() as $update_info) {
            $obj_num = $update_info->get_obj_num();
            if ($obj_num - $last_obj_num != 1) {
                // Save cross-reference table subsection and start new one
                $xref_table[] = $xref_section;
                $xref_section = [];
                $xref_section_start_nums[] = $obj_num;
            }
            if ($update_info->is_free()) {
                // Free object cross-reference table entry
                $xref_section[] = sprintf("%010d %05d f \n", $last_free_object, $update_info->get_gen_num());
                $last_free_object = $obj_num;
            } else {
                // In-use object cross-reference table entry
                $xref_section[] = sprintf("%010d %05d n \n", $offset, $update_info->get_gen_num());
                $pdf_block = $update_info->get_object_dump();
                $offset += strlen($pdf_block);
                if ($output_stream === null) {
                    $pdf_segment_blocks[] = $pdf_block;
                } else {
                    while (strlen($pdf_block) > 0 && ($byte_count = fwrite($output_stream, $pdf_block)) != false) {
                        $pdf_block = substr($pdf_block, $byte_count);
                    }
                }
            }
            $last_obj_num = $obj_num;
        }
        // Save last cross-reference table subsection
        $xref_table[] = $xref_section;
        // Modify first entry (specail case - header of linked list of free objects).
        $xref_table[0][0] = sprintf("%010d 65535 f \n", $last_free_object);
        $xref_table_str = "xref\n";
        foreach ($xref_table as $sect_id => $xref_section) {
            $xref_table_str .= sprintf("%d %d \n", $xref_section_start_nums[$sect_id], count($xref_section));
            foreach ($xref_section as $xref_table_entry) {
                $xref_table_str .= $xref_table_entry;
            }
        }
        $this->_trailer->Size->value = $this->_obj_factory->get_object_count();
        $pdf_block = $xref_table_str . $this->_trailer->to_string() . "startxref\n" . $offset . "\n" . "%%EOF\n";
        $this->_obj_factory->clean_enumeration_shift_cache();
        if ($output_stream === null) {
            $pdf_segment_blocks[] = $pdf_block;
            return implode('', $pdf_segment_blocks);
        }
        while (strlen($pdf_block) > 0 && ($byte_count = fwrite($output_stream, $pdf_block)) != false) {
            $pdf_block = substr($pdf_block, $byte_count);
        }
        return '';
    }
    /**
     * Sets the document-level JavaScript
     *
     * Resets and appends
     *
     * @param string|array $javaScript
     */
    public function set_java_script($java_script)
    {
        $this->reset_java_script();
        $this->add_java_script($java_script);
    }
    /**
     * Resets the document-level JavaScript
     */
    public function reset_java_script()
    {
        $this->_java_script = null;
        $root = $this->_trailer->Root;
        if (null === $root->Names || null === $root->Names->java_script) {
            return;
        }
        $root->Names->java_script = null;
    }
    /**
     * Appends JavaScript to the document-level JavaScript
     *
     * @param string|array $javaScript
     * @throws Zend_Pdf_Exception
     */
    public function add_java_script($java_script)
    {
        if (empty($java_script)) {
            throw new Zend_Pdf_Exception('JavaScript must be a non empty string or array of strings');
        }
        if (!is_array($java_script)) {
            $java_script = [$java_script];
        }
        if (null === $this->_java_script) {
            $this->_java_script = $java_script;
        } else {
            $this->_java_script = array_merge($this->_java_script, $java_script);
        }
        if (!empty($this->_java_script)) {
            $items = [];
            foreach ($this->_java_script as $java_script) {
                $js_code = ['S' => new Zend_Pdf_Element_Name('JavaScript'), 'JS' => new Zend_Pdf_Element_String($java_script)];
                $items[] = new Zend_Pdf_Element_String('EmbeddedJS');
                $items[] = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary($js_code));
            }
            $js_ref = $this->_obj_factory->new_object(new Zend_Pdf_Element_Dictionary(['Names' => new Zend_Pdf_Element_Array($items)]));
            if (null === $this->_trailer->Root->Names) {
                $this->_trailer->Root->Names = new Zend_Pdf_Element_Dictionary();
            }
            $this->_trailer->Root->Names->java_script = $js_ref;
        }
    }
    /**
     * Convert date to PDF format (it's close to ASN.1 (Abstract Syntax Notation
     * One) defined in ISO/IEC 8824).
     *
     * @todo This really isn't the best location for this method. It should
     *   probably actually exist as Zend_Pdf_Element_Date or something like that.
     *
     * @todo Address the following E_STRICT issue:
     *   PHP Strict Standards:  date(): It is not safe to rely on the system's
     *   timezone settings. Please use the date.timezone setting, the TZ
     *   environment variable or the date_default_timezone_set() function. In
     *   case you used any of those methods and you are still getting this
     *   warning, you most likely misspelled the timezone identifier.
     *
     * @param integer $timestamp (optional) If omitted, uses the current time.
     * @return string
     */
    public static function pdf_date($timestamp = null)
    {
        if ($timestamp === null) {
            $date = date('\D\:YmdHisO');
        } else {
            $date = date('\D\:YmdHisO', $timestamp);
        }
        return substr_replace($date, '\'', -2, 0) . '\'';
    }
}