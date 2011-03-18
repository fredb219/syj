<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class FaqController extends Zend_Controller_Action
{
    public function indexAction() {
        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/faq.css', 'all');
        $this->view->headTitle($this->view->translate("Frequently asked questions"));
    }
}
