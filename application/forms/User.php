<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_User extends Syj_Form_TableAbstract
{
    public function init() {
        $translator = $this->getTranslator();

        $desc = $translator->translate("only letters, numbers, underscores or dots");
        $name = array('Text', 'user_pseudo', array(
            'label' => __("user name"),
            'attribs' => array('maxlength' => '20', 'autocomplete' => 'off'),
            'description' => $desc,
            'validators' => array(new Zend_Validate_StringLength(0, 20),
                                 new Zend_Validate_Regex('/^[a-zA-Z0-9_\.]+$/')),
            'required' => true
        ));

        $desc = $translator->translate("At least %d characters");
        $desc = vsprintf($desc, 6);
        $pass = array('Password', 'user_password', array(
            'label' => __("password"),
            'required' => true,
            'description' => $desc,
            'validators' => array(new Zend_Validate_StringLength(6))
        ));

        $pass_confirm = array('Password', 'user_password_confirm', array(
                'label' => __("confirm password"),
                'validators' => array(new Zend_Validate_Identical('user_password')),
                'required' => true
        ));

        $email = array('Text', 'user_email', array(
            'label' => __("email"),
            'description' => __("After creating your account, you will receive a confirmation email. You have 7 days to confirm otherwise, your account and your routes will all be deleted."),
            'required' => true
            ));

        $this->setMainElements(array($name, $pass, $pass_confirm, $email));

        $anchor = $this->getView()->Anchor("termsofuse?format=raw",
                                           $translator->translate("terms of use"),
                                           array('id' => 'user_termsofuse_anchor'));
        $text = $translator->translate("I've read and accepted %s");
        $text = vsprintf($text, $anchor);
        $this->addElement('Checkbox', 'user_accept', array("label" => $text,
                            'decorators' => array(
                                  'ViewHelper',
                                  'label',
                                  array('HtmlTag', array('tag' => 'div', 'id' => 'user_accept_container'))),
                            'validators' => array(new Zend_Validate_Identical('1'))));

        $decorator = $this->user_accept->getDecorator('Zend_Form_Decorator_Label');
        $decorator->setOption('escape', false);

        $this->addElement('Submit', 'user_submit', array('label' => __("create account")));
    }
}
