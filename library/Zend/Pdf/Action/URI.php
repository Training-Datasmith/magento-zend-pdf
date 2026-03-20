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
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/String.php';
#require_once 'Zend/Pdf/Element/Boolean.php';
/** Zend_Pdf_Action */
#require_once 'Zend/Pdf/Action.php';
/**
 * PDF 'Resolve a uniform resource identifier' action
 *
 * A URI action causes a URI to be resolved.
 *
 * @package    Zend_Pdf
 * @subpackage Actions
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Action_URI extends Zend_Pdf_Action
{
    /**
     * Object constructor
     *
     * @param Zend_Pdf_Element_Dictionary $dictionary
     * @param SplObjectStorage            $processedActions  list of already processed action dictionaries, used to avoid cyclic references
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_Element $dictionary, Spl_Object_Storage $processed_actions)
    {
        parent::__construct($dictionary, $processed_actions);
        if ($dictionary->URI === null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('URI action dictionary entry is required');
        }
    }
    /**
     * Validate URI
     *
     * @param string $uri
     * @return true
     * @throws Zend_Pdf_Exception
     */
    protected static function _validate_uri($uri)
    {
        $scheme = parse_url((string) $uri, PHP_URL_SCHEME);
        if ($scheme === false || $scheme === null) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Invalid URI');
        }
    }
    /**
     * Create new Zend_Pdf_Action_URI object using specified uri
     *
     * @param string  $uri    The URI to resolve, encoded in 7-bit ASCII
     * @param boolean $isMap  A flag specifying whether to track the mouse position when the URI is resolved
     */
    public static function create($uri, $is_map = false): \Zend_Pdf_Action_URI
    {
        self::_validate_uri($uri);
        $dictionary = new Zend_Pdf_Element_Dictionary();
        $dictionary->Type = new Zend_Pdf_Element_Name('Action');
        $dictionary->S = new Zend_Pdf_Element_Name('URI');
        $dictionary->Next = null;
        $dictionary->URI = new Zend_Pdf_Element_String($uri);
        if ($is_map) {
            $dictionary->is_map = new Zend_Pdf_Element_Boolean(true);
        }
        return new Zend_Pdf_Action_URI($dictionary, new Spl_Object_Storage());
    }
    /**
     * Set URI to resolve
     *
     * @param string $uri   The uri to resolve, encoded in 7-bit ASCII.
     */
    public function set_uri($uri): self
    {
        static::_validate_uri($uri);
        $this->_action_dictionary->touch();
        $this->_action_dictionary->URI = new Zend_Pdf_Element_String($uri);
        return $this;
    }
    /**
     * Get URI to resolve
     *
     * @return string
     */
    public function get_uri()
    {
        return $this->_action_dictionary->URI->value;
    }
    /**
     * Set IsMap property
     *
     * If the IsMap flag is true and the user has triggered the URI action by clicking
     * an annotation, the coordinates of the mouse position at the time the action is
     * performed should be transformed from device space to user space and then offset
     * relative to the upper-left corner of the annotation rectangle.
     *
     * @param boolean $isMap  A flag specifying whether to track the mouse position when the URI is resolved
     */
    public function set_is_map($is_map): self
    {
        $this->_action_dictionary->touch();
        if ($is_map) {
            $this->_action_dictionary->is_map = new Zend_Pdf_Element_Boolean(true);
        } else {
            $this->_action_dictionary->is_map = null;
        }
        return $this;
    }
    /**
     * Get IsMap property
     *
     * If the IsMap flag is true and the user has triggered the URI action by clicking
     * an annotation, the coordinates of the mouse position at the time the action is
     * performed should be transformed from device space to user space and then offset
     * relative to the upper-left corner of the annotation rectangle.
     */
    public function get_is_map(): bool
    {
        return $this->_action_dictionary->is_map !== null && $this->_action_dictionary->is_map->value;
    }
}