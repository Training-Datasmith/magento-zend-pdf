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
 * @subpackage Annotation
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
/** Internally used classes */
#require_once 'Zend/Pdf/Element.php';
#require_once 'Zend/Pdf/Element/Array.php';
#require_once 'Zend/Pdf/Element/Dictionary.php';
#require_once 'Zend/Pdf/Element/Name.php';
#require_once 'Zend/Pdf/Element/Numeric.php';
/** Zend_Pdf_Annotation */
#require_once 'Zend/Pdf/Annotation.php';
/**
 * A link annotation represents either a hypertext link to a destination elsewhere in
 * the document or an action to be performed.
 *
 * Only destinations are used now since only GoTo action can be created by user
 * in current implementation.
 *
 * @package    Zend_Pdf
 * @subpackage Annotation
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Pdf_Annotation_Link extends Zend_Pdf_Annotation
{
    /**
     * Annotation object constructor
     *
     * @throws Zend_Pdf_Exception
     */
    public function __construct(Zend_Pdf_Element $annotation_dictionary)
    {
        if ($annotation_dictionary->get_type() != Zend_Pdf_Element::TYPE_DICTIONARY) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Annotation dictionary resource has to be a dictionary.');
        }
        if ($annotation_dictionary->Subtype === null || $annotation_dictionary->Subtype->get_type() != Zend_Pdf_Element::TYPE_NAME || $annotation_dictionary->Subtype->value != 'Link') {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('Subtype => Link entry is requires');
        }
        parent::__construct($annotation_dictionary);
    }
    /**
     * Create link annotation object
     *
     * @param float                  $x1
     * @param float                  $y1
     * @param float                  $x2
     * @param float                  $y2
     * @param Zend_Pdf_Target|string $target
     * @throws Zend_Pdf_Exception
     */
    public static function create($x1, $y1, $x2, $y2, $target): \Zend_Pdf_Annotation_Link
    {
        if (is_string($target)) {
            #require_once 'Zend/Pdf/Destination/Named.php';
            $target = Zend_Pdf_Destination_Named::create($target);
        }
        if (!$target instanceof Zend_Pdf_Target) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('$target parameter must be a Zend_Pdf_Target object or a string.');
        }
        $annotation_dictionary = new Zend_Pdf_Element_Dictionary();
        $annotation_dictionary->Type = new Zend_Pdf_Element_Name('Annot');
        $annotation_dictionary->Subtype = new Zend_Pdf_Element_Name('Link');
        $rectangle = new Zend_Pdf_Element_Array();
        $rectangle->items[] = new Zend_Pdf_Element_Numeric($x1);
        $rectangle->items[] = new Zend_Pdf_Element_Numeric($y1);
        $rectangle->items[] = new Zend_Pdf_Element_Numeric($x2);
        $rectangle->items[] = new Zend_Pdf_Element_Numeric($y2);
        $annotation_dictionary->Rect = $rectangle;
        if ($target instanceof Zend_Pdf_Destination) {
            $annotation_dictionary->Dest = $target->get_resource();
        } else {
            $annotation_dictionary->A = $target->get_resource();
        }
        return new Zend_Pdf_Annotation_Link($annotation_dictionary);
    }
    /**
     * Set link annotation destination
     *
     * @param Zend_Pdf_Target|string $target
     */
    public function set_destination($target): self
    {
        if (is_string($target)) {
            #require_once 'Zend/Pdf/Destination/Named.php';
            $destination = Zend_Pdf_Destination_Named::create($target);
        }
        if (!$target instanceof Zend_Pdf_Target) {
            #require_once 'Zend/Pdf/Exception.php';
            throw new Zend_Pdf_Exception('$target parameter must be a Zend_Pdf_Target object or a string.');
        }
        $this->_annotation_dictionary->touch();
        $this->_annotation_dictionary->Dest = $destination->get_resource();
        if ($target instanceof Zend_Pdf_Destination) {
            $this->_annotation_dictionary->Dest = $target->get_resource();
            $this->_annotation_dictionary->A = null;
        } else {
            $this->_annotation_dictionary->Dest = null;
            $this->_annotation_dictionary->A = $target->get_resource();
        }
        return $this;
    }
    /**
     * Get link annotation destination
     *
     * @return Zend_Pdf_Target|null
     */
    public function get_destination()
    {
        if ($this->_annotation_dictionary->Dest === null && $this->_annotation_dictionary->A === null) {
            return null;
        }
        if ($this->_annotation_dictionary->Dest !== null) {
            #require_once 'Zend/Pdf/Destination.php';
            return Zend_Pdf_Destination::load($this->_annotation_dictionary->Dest);
        }
        #require_once 'Zend/Pdf/Action.php';
        return Zend_Pdf_Action::load($this->_annotation_dictionary->A);
    }
}