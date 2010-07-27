<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Account extends Zend_Form
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

        $validator = new Syj_Validate_EmailAddress();
        $email = array('Text', 'account_email', array(
            'label' => __("email"),
            'validators' => array($validator),
            'maxlength' => '320',
            'required' => true));

        $passValidator = new Zend_Validate_StringLength(6);
        $passValidator->setMessage(vsprintf($this->getTranslator()->translate("At least %d characters"), 6));
        $pass = array('Password', 'account_password', array(
            'label' => __("password"),
            'required' => true,
            'validators' => array($passValidator)));

        $identicalValidator = new Zend_Validate_Identical('account_password');
        $identicalValidator->setMessage(__("Password do not match"));
        $pass_confirm = array('Password', 'account_password_confirm', array(
                'label' => __("confirm password"),
                'validators' => array($identicalValidator),
                'required' => true
        ));

        $pass_current = array('Password', 'account_password_current', array(
                'label' => __("current password")));

        $submit = array('Submit', 'account_submit', array('label' => __("modify my informations")));

        $this->addElements(array($email, $pass, $pass_confirm, $pass_current, $submit));
        $this->account_submit->setDecorators(array('ViewHelper'));

        // fieldset around form
        $this->addDisplayGroup(array_keys($this->_elements), 'main',
            array('decorators' => array('FormElements',
                                 array('fieldset'))));

    }
}
