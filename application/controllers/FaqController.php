<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class FaqController extends Zend_Controller_Action
{
    public function indexAction() {
        $this->_helper->SyjMedias->addStyleSheets('faq');
        $this->view->headTitle($this->view->translate("Frequently asked questions"));
    }
}
