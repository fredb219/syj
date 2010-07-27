<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Newpwd extends Syj_Form_TableAbstract
{
    protected $_elementDecorators = array(
                          'ViewHelper',
                          array(array('td' => 'HtmlTag'), array('tag' => 'td', 'style' => 'width: 100%' )),
                          array('label', array('tag' => 'td')),
                          array(array('tr' => 'HtmlTag'), array('tag' => 'tr')),
                          array('TableDescription'),
                          'Errors' => array('decorator' => 'Errors', 'options' => array('class' => 'error')),
                          );

    protected $_decorators = array(
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => null),
                            );


    public function init() {
        $formErrors = $this->getView()->getHelper('FormErrors');
        $formErrors->setElementStart("<div%s>")
                   ->setElementEnd("</div>")
                   ->setElementSeparator("<br>");

        $user = array('Text', 'newpwd_email', array( 'label' => __("email"),
                                                     'required' => true,
                                                     'maxlength' => 320
                                                     ));
        $this->setMainElements(array($user));
        $this->addElement('Submit', 'newpwd_submit', array('label' => __("reset my password")));
    }
}

