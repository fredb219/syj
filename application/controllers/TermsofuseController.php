<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class TermsofuseController extends Zend_Controller_Action
{
    public function indexAction() {
        $this->_helper->SyjMedias->addStyleSheets('termsofuse');
        $this->view->headTitle($this->view->translate("terms of use"));
        $this->view->rawmode = ($this->getRequest()->getQuery('format') == 'raw');
    }
}
