<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

abstract class Syj_Form_TableAbstract extends Zend_Form
{
    protected $_elementDecorators = array(
                          'ViewHelper',
                          array(array('td' => 'HtmlTag'), array('tag' => 'td', 'style' => 'width: 100%' )),
                          array('label', array('tag' => 'td')),
                          array(array('tr' => 'HtmlTag'), array('tag' => 'tr')),
                          array('TableDescription'));

    protected $_decorators = array(
                            'FormErrors' => array('decorator' => 'FormErrors', 'options' => null),
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => null),
                            );

    protected $_displaygroupDecorators = array(
                'FormElements',
                array('HtmlTag', array('tag' => 'table')));

    protected $_defaultClassName = "form-table-elem";

    public function __construct($options = null) {
        $this->addPrefixPath('Syj_Form_Decorator', APPLICATION_PATH . '/forms/decorator/', 'decorator');
        parent::__construct($options);
    }

    protected function setMainElements(array $elements) {
        $this->setElements($elements);
        $this->addDisplayGroup(array_map(function ($elem) { return $elem[1]; }, $elements), 'data');
        $this->setDisplayGroupDecorators($this->_displaygroupDecorators);

        return $this;
    }

    public function addElement($element, $name = null, $options = null)
    {
        $res = parent::addElement($element, $name, $options);
        if ($element instanceof Zend_Form_Element) {
            $name = $element->getName();
        }
        $element = $this->_elements[$name];
        if ($element instanceof Zend_Form_Element_Text or $element instanceof Zend_Form_Element_Password) {
            if ($this->getAttrib('class') === null) {
                $element->setAttrib('class', $this->_defaultClassName);
            }
        } else if ($element instanceof Zend_Form_Element_Submit) {
            $element->setDecorators(array(
                        'ViewHelper',
                        array('Description', array('tag' => '')),
                        array('HtmlTag', array('tag' => 'p', 'class' => 'center')),
                        ));
        }
        return $res;
    }
}
