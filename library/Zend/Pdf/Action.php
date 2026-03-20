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
 * @subpackage Actions
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Internally used classes */
#require_once 'Zend/Pdf/Element.php';
#require_once 'Zend/Pdf/Element/Array.php';
/** Zend_Pdf_Target */
#require_once 'Zend/Pdf/Target.php';
/**
 * Abstract PDF action representation class
 *
 * @package    Zend_Pdf
 * @subpackage Actions
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Zend_Pdf_Action extends Zend_Pdf_Target implements Recursive_Iterator, Countable
{
    /**
     * Action dictionary
     *
     * @var Zend_Pdf_Element_Dictionary|Zend_Pdf_Element_Object|Zend_Pdf_Element_Reference
     */
    protected $_action_dictionary;
    /**
     * An original list of chained actions
     *
     * @var array  Array of Zend_Pdf_Action objects
     */
    protected $_original_next_list;
    /**
     * A list of next actions in actions tree (used for actions chaining)
     *
     * @var array  Array of Zend_Pdf_Action objects
     */
    public $next = [];
    /**
     * Object constructor
     *
     * @param Zend_Pdf_Element_Dictionary $dictionary
     * @param SplObjectStorage            $processedActions  list of already processed action dictionaries, used to avoid cyclic references
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_Element $dictionary, Spl_Object_Storage $processed_actions)
    {
        #require_once 'Zend/Pdf/Element.php';
        if ($dictionary->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('$dictionary mast be a direct or an indirect dictionary object.');
        }
        $this->_action_dictionary = $dictionary;
        if ($dictionary->Next !== null) {
            if ($dictionary->Next instanceof Zend_Pdf_Element_Dictionary) {
                // Check if dictionary object is not already processed
                if (!$processed_actions->contains($dictionary->Next)) {
                    $processed_actions->attach($dictionary->Next);
                    $this->next[] = Zend_Pdf_Action::load($dictionary->Next, $processed_actions);
                }
            } elseif ($dictionary->Next instanceof Zend_Pdf_Element_Array) {
                foreach ($dictionary->Next->items as $chained_action_dictionary) {
                    // Check if dictionary object is not already processed
                    if (!$processed_actions->contains($chained_action_dictionary)) {
                        $processed_actions->attach($chained_action_dictionary);
                        $this->next[] = Zend_Pdf_Action::load($chained_action_dictionary, $processed_actions);
                    }
                }
            } else {
                #require_once 'Zend/Pdf/Exception.php';
                throw new Zend_Pdf_Exception('PDF Action dictionary Next entry must be a dictionary or an array.');
            }
        }
        $this->_original_next_list = $this->next;
    }
    /**
     * Load PDF action object using specified dictionary
     *
     * @internal
     * @param Zend_Pdf_Element $dictionary (It's actually Dictionary or Dictionary Object or Reference to a Dictionary Object)
     * @param SplObjectStorage $processedActions  list of already processed action dictionaries, used to avoid cyclic references
     * @return Zend_Pdf_Action
     * @throws Zend_Pdf_Exception
     */
    public static function load(Zend_Pdf_Element $dictionary, ?Spl_Object_Storage $processed_actions = null)
    {
        if ($processed_actions === null) {
            $processed_actions = new Spl_Object_Storage();
        }
        #require_once 'Zend/Pdf/Element.php';
        if ($dictionary->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('$dictionary mast be a direct or an indirect dictionary object.');
        }
        if (isset($dictionary->Type) && $dictionary->Type->value != 'Action') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Action dictionary Type entry must be set to \'Action\'.');
        }
        if ($dictionary->S === null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Action dictionary must contain S entry');
        }
        switch ($dictionary->S->value) {
            case 'GoTo':
                #require_once 'Zend/Pdf/Action/GoTo.php';
                return new Zend_pdf_action_go_To($dictionary, $processed_actions);
            case 'GoToR':
                #require_once 'Zend/Pdf/Action/GoToR.php';
                return new Zend_pdf_action_go_To_R($dictionary, $processed_actions);
            case 'GoToE':
                #require_once 'Zend/Pdf/Action/GoToE.php';
                return new Zend_pdf_action_go_To_E($dictionary, $processed_actions);
            case 'Launch':
                #require_once 'Zend/Pdf/Action/Launch.php';
                return new Zend_Pdf_Action_Launch($dictionary, $processed_actions);
            case 'Thread':
                #require_once 'Zend/Pdf/Action/Thread.php';
                return new Zend_Pdf_Action_Thread($dictionary, $processed_actions);
            case 'URI':
                #require_once 'Zend/Pdf/Action/URI.php';
                return new Zend_Pdf_Action_URI($dictionary, $processed_actions);
            case 'Sound':
                #require_once 'Zend/Pdf/Action/Sound.php';
                return new Zend_Pdf_Action_Sound($dictionary, $processed_actions);
            case 'Movie':
                #require_once 'Zend/Pdf/Action/Movie.php';
                return new Zend_Pdf_Action_Movie($dictionary, $processed_actions);
            case 'Hide':
                #require_once 'Zend/Pdf/Action/Hide.php';
                return new Zend_Pdf_Action_Hide($dictionary, $processed_actions);
            case 'Named':
                #require_once 'Zend/Pdf/Action/Named.php';
                return new Zend_Pdf_Action_Named($dictionary, $processed_actions);
            case 'SubmitForm':
                #require_once 'Zend/Pdf/Action/SubmitForm.php';
                return new Zend_pdf_action_submit_Form($dictionary, $processed_actions);
            case 'ResetForm':
                #require_once 'Zend/Pdf/Action/ResetForm.php';
                return new Zend_pdf_action_reset_Form($dictionary, $processed_actions);
            case 'ImportData':
                #require_once 'Zend/Pdf/Action/ImportData.php';
                return new Zend_pdf_action_import_Data($dictionary, $processed_actions);
            case 'JavaScript':
                #require_once 'Zend/Pdf/Action/JavaScript.php';
                return new Zend_pdf_action_java_Script($dictionary, $processed_actions);
            case 'SetOCGState':
                #require_once 'Zend/Pdf/Action/SetOCGState.php';
                return new Zend_pdf_action_set_Ocg_State($dictionary, $processed_actions);
            case 'Rendition':
                #require_once 'Zend/Pdf/Action/Rendition.php';
                return new Zend_Pdf_Action_Rendition($dictionary, $processed_actions);
            case 'Trans':
                #require_once 'Zend/Pdf/Action/Trans.php';
                return new Zend_Pdf_Action_Trans($dictionary, $processed_actions);
            case 'GoTo3DView':
                #require_once 'Zend/Pdf/Action/GoTo3DView.php';
                return new Zend_pdf_action_go_To3d_View($dictionary, $processed_actions);
            default:
                #require_once 'Zend/Pdf/Action/Unknown.php';
                return new Zend_Pdf_Action_Unknown($dictionary, $processed_actions);
        }
    }
    /**
     * Get resource
     *
     * @internal
     * @return Zend_Pdf_Element
     */
    public function get_resource()
    {
        return $this->_action_dictionary;
    }
    /**
     * Dump Action and its child actions into PDF structures
     *
     * Returns dictionary indirect object or reference
     *
     * @internal
     * @param Zend_Pdf_ElementFactory $factory   Object factory for newly created indirect objects
     * @param SplObjectStorage $processedActions  list of already processed actions (used to prevent infinity loop caused by cyclic references)
     * @return Zend_Pdf_Element_Object|Zend_Pdf_Element_Reference   Dictionary indirect object
     */
    public function dump_action(Zend_pdf_element_Factory_interface $factory, ?Spl_Object_Storage $processed_actions = null)
    {
        if ($processed_actions === null) {
            $processed_actions = new Spl_Object_Storage();
        }
        if ($processed_actions->contains($this)) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Action chain cyclyc reference is detected.');
        }
        $processed_actions->attach($this);
        $child_list_updated = false;
        if (count($this->_original_next_list) != count($this->next)) {
            // If original and current children arrays have different size then children list was updated
            $child_list_updated = true;
        } elseif (!(array_keys($this->_original_next_list) === array_keys($this->next))) {
            // If original and current children arrays have different keys (with a glance to an order) then children list was updated
            $child_list_updated = true;
        } else {
            foreach ($this->next as $key => $child_action) {
                if ($this->_original_next_list[$key] !== $child_action) {
                    $child_list_updated = true;
                    break;
                }
            }
        }
        if ($child_list_updated) {
            $this->_action_dictionary->touch();
            switch (count($this->next)) {
                case 0:
                    $this->_action_dictionary->Next = null;
                    break;
                case 1:
                    $child = reset($this->next);
                    $this->_action_dictionary->Next = $child->dump_action($factory, $processed_actions);
                    break;
                default:
                    #require_once 'Zend/Pdf/Element/Array.php';
                    $pdf_child_array = new Zend_Pdf_Element_Array();
                    foreach ($this->next as $child) {
                        $pdf_child_array->items[] = $child->dump_action($factory, $processed_actions);
                    }
                    $this->_action_dictionary->Next = $pdf_child_array;
                    break;
            }
        } else {
            foreach ($this->next as $child) {
                $child->dump_action($factory, $processed_actions);
            }
        }
        if ($this->_action_dictionary instanceof Zend_Pdf_Element_Dictionary) {
            // It's a newly created action. Register it within object factory and return indirect object
            return $factory->new_object($this->_action_dictionary);
        }
        // It's a loaded object
        return $this->_action_dictionary;
    }
    ////////////////////////////////////////////////////////////////////////
    //  RecursiveIterator interface methods
    //////////////
    /**
     * Returns current child action.
     *
     * @return Zend_Pdf_Action
     */
    #[\Return_Type_Will_Change]
    public function current()
    {
        return current($this->next);
    }
    /**
     * Returns current iterator key
     *
     * @return integer
     */
    #[\Return_Type_Will_Change]
    public function key()
    {
        return key($this->next);
    }
    /**
     * Go to next child
     */
    #[\Return_Type_Will_Change]
    public function next()
    {
        return next($this->next);
    }
    /**
     * Rewind children
     */
    #[\Return_Type_Will_Change]
    public function rewind()
    {
        return reset($this->next);
    }
    /**
     * Check if current position is valid
     *
     * @return boolean
     */
    #[\Return_Type_Will_Change]
    public function valid()
    {
        return current($this->next) !== false;
    }
    /**
     * Returns the child action.
     *
     * @return Zend_Pdf_Action|null
     */
    #[\Return_Type_Will_Change]
    public function get_children()
    {
        return current($this->next);
    }
    /**
     * Implements RecursiveIterator interface.
     *
     * @return bool  whether container has any pages
     */
    #[\Return_Type_Will_Change]
    public function has_children()
    {
        return count($this->next) > 0;
    }
    ////////////////////////////////////////////////////////////////////////
    //  Countable interface methods
    //////////////
    /**
     * count()
     *
     * @return int
     */
    #[\Return_Type_Will_Change]
    public function count()
    {
        return count($this->next);
    }
}