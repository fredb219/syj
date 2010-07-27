<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Contact extends Zend_Form
{

    protected $_decorators = array(
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => array()));

    protected $_elementDecorators = array(
                                'ViewHelper',
                                'Errors' => array('decorator' => 'Errors', 'options' => array('class' => 'error')),
                                array(array('lbreak1' => 'HtmlTag'), array('tag' => 'br', 'openOnly' => true)),
                                'Label',
                                array(array('lbreak2' => 'HtmlTag'), array('tag' => 'br', 'placement' => 'APPEND', 'openOnly' => true)),
                                );

    public function init() {
        $formErrors = $this->getView()->getHelper('FormErrors');
        $formErrors->setElementStart("<div%s>")
                   ->setElementEnd("</div>")
                   ->setElementSeparator("<br>");

        // required needs following string to be translated
        __("Value is required and can't be empty");

        // we are less strict than Zend_Validate_Email because we want a user
        // with a strange email to be able to still contact us with the form
        $emailValidator = new Zend_Validate_Regex('/^[A-Z0-9._-]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z.]{2,6}$/i');
        $emailValidator->setMessage(__("Invalid email"), $emailValidator::NOT_MATCH);
        $email = array('Text', 'contact_email', array(
             'required' => 'true',
             'validators' => array($emailValidator),
            'label' => __("Email:")));

        $maxSubjLength = 80;
        $subjectValidator = new Zend_Validate_StringLength(0, $maxSubjLength);
        $subjectValidator->setMessage(__("Subject must be %max% characters long or less",$subjectValidator::TOO_LONG));
        $subject = array('Text', 'contact_subject', array(
             'required' => 'true',
             'maxlength' => $maxSubjLength,
             'validators' => array($subjectValidator),
            'label' => __("Subject:")));

        $contentFilter = new Zend_Filter_PregReplace('/\r\n/', "\n");
        $content = array('Textarea', 'contact_content', array(
            'label' => __("Message:"),
            'cols' => 40,
            'filters' => array($contentFilter),
            'required' => 'true',
            'rows' => 10));

        $submit = array('Submit', 'contact_submit', array(
            'label' => __("Send"),
            'decorators' => array('ViewHelper')));

        $this->addElements(array($email, $subject, $content, $submit));

        // fieldset around form
        $this->addDisplayGroup(array_keys($this->_elements), 'main',
            array('decorators' => array('FormElements',
                                 array('fieldset', array('legend' => __("Send a message")))
        )));
    }

}
