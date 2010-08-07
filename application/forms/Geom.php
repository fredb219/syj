<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Geom extends Zend_Form
{
    protected $_elementDecorators = array( 'ViewHelper', 'Errors',);

    protected $_decorators = array(
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => null)
                            );

    public function init() {
        $id = array('Hidden', 'geom_id');
        $data = array('Hidden', 'geom_data', array('required' => true));

        $title = array('Text', 'geom_title', array(
            'label' => __("optional title for this journey"),
            'attribs' => array('maxlength' => '40', 'size' => '20'),
            'validators' => array(new Zend_Validate_StringLength(0, 40))
            ));

        $translator = $this->getTranslator();
        $anchor = $this->getView()->Anchor("termsofuse", $translator->translate("terms of use"), array('id' => 'geom_termsofuse_anchor'));
        $text = $translator->translate("I've read and accepted %s");
        $text = vsprintf($text, $anchor);
        $touaccept = array('Checkbox', 'geom_accept', array("label" => $text,
                            'decorators' => array(
                                  'ViewHelper',
                                  'label',
                                  array('HtmlTag', array('tag' => 'div', 'id' => 'geom_accept_container')))));

        $submit = array('Submit', 'geom_submit', array('label' => __("save")));

        $this->addElements(array($id, $data, $title, $touaccept, $submit));

        $decorator = $this->geom_accept->getDecorator('Zend_Form_Decorator_Label');
        $decorator->setOption('escape', false);

        $this->geom_title->addDecorator('HtmlTag', array('tag' => 'br', 'openOnly' => true))->
            addDecorator('label');
        $this->geom_submit->addDecorator('HtmlTag', array('tag' => 'br', 'openOnly' => true));

    }
}
