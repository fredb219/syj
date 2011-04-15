<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class ListController extends Zend_Controller_Action
{

    public function init() {
        $this->_helper->SyjUserManager->needsLogin();

        $this->_helper->SyjMedias->addScripts('list');
        $this->_helper->SyjMedias->addStyleSheets('list');

        $this->view->headTitle($this->view->translate("my routes"));
    }

    public function indexAction() {
        $user = $this->_helper->SyjUserManager->current();
        $pathMapper = new Syj_Model_PathMapper();
        $list = $pathMapper->fetchByCreator($user);
        $paginator = Zend_Paginator::factory($list);

        $paginator->setDefaultItemCountPerPage(20);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $this->view->paginator = $paginator;
        $this->_jsLocaleStrings();
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'confirmDelete' => __("There is no undo. Delete this route definitively ?"),
            'notReachedError' => __("server could not be reached"),
            'requestError' => __("server did not understood request. That's probably caused by a bug in SYJ"),
            'gonePathError' => __("route not referenced on the server. It has probably been deleted."),
            'serverError' => __("there was a server error"),
            'unknownError' => __("there was an unknown error"),
            'deleteSuccess' => __("route was successfully deleted"),
            );
    }

}
