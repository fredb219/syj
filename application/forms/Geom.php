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
        $submit = array('Submit', 'geom_submit', array('label' => __("save")));

        $this->addElements(array($id, $data, $title, $submit));

        // fieldset around title
        //$this->addDisplayGroup(array('geom_title'), 'metadata', array('decorators' => array('FormElements', 'Fieldset')));

        $this->geom_title->addDecorator('HtmlTag', array('tag' => 'br', 'openOnly' => true))->
            addDecorator('label');
        $this->geom_submit->addDecorator('HtmlTag', array('tag' => 'br', 'openOnly' => true));

    }
}
