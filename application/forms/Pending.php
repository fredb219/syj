<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

abstract class Syj_Form_Pending extends Zend_Form
{
    protected $_pending = null;

    protected $_elementDecorators = array(
                          array('Description', array('tag' => '', 'escape' => false)),
                          array(array('br' => 'HtmlTag'), array('tag' => 'br', 'placement' => 'APPEND', 'openOnly' => true)),
                          'ViewHelper',
                          array(array('p' => 'HtmlTag'), array('tag' => 'p', 'class' => 'desc')),
                          );

    protected $_decorators = array(
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => array('class' => 'center'))
                            );

    public function __construct(Syj_Model_Pending $pending, $options = null) {
        $this->_pending = $pending;
        parent::__construct($options);
    }

    public function init() {
        $activate = array('Submit', 'pending_validate', $this->getActivateOptions());
        $cancel = array('Submit', 'pending_cancel', $this->getCancelOptions());
        $this->addElements(array($activate, $cancel));
    }

    abstract protected function getActivateOptions();

    abstract protected function getCancelOptions();

}
