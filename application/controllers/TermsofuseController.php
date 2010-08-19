<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class TermsofuseController extends Zend_Controller_Action
{
    public function indexAction() {
        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/termsofuse.css', 'all');
        $this->view->headTitle($this->view->translate("terms of use"));
        $this->view->rawmode = ($this->getRequest()->getQuery('format') == 'raw');
    }
}
