<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class PendingController extends Zend_Controller_Action
{

    public function init() {
        $this->view->headLink()->appendStylesheet('css/generic.css');
        $this->view->headLink()->appendStylesheet('css/pending.css');
    }

    public function indexAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $hash = $request->idx;

        $pendingMapper = new Syj_Model_PendingMapper();
        $pending = $pendingMapper->fetchByHash($hash);
        if (!isset($pending)) {
            throw new Syj_Exception_NotFound('Not Found', 404);
        }

        $formData = $request->getPost();

        switch ($pending->action) {
            case 'validate_creation':
            if (array_key_exists('pending_validate', $formData)) {
                    if (!$pending->run()) {
                        throw new Syj_Exception();
                    }
                    $title = $this->view->translate("account validated");
                    $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/creation_validate.:suffix');

                } else if (array_key_exists('pending_cancel', $formData)) {
                    if (!$pending->cancel()) {
                        throw new Syj_Exception();
                    }
                    $title = $this->view->translate("account deleted");
                    $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/creation_cancel.:suffix');
                } else {
                    $this->view->form = new Syj_Form_Pending_ValidateCreation($pending, array('name' => 'pendingform'));
                    $title = $this->view->translate('account creation validation');
                }
            break;
            case 'reset_password':
                if (array_key_exists('pending_validate', $formData)) {
                    if (!$pending->run()) {
                        throw new Syj_Exception();
                    }
                    $this->view->newpwd = $pending->newpwd;

                    $title = $this->view->translate("password changed");
                    $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/password_validate.:suffix');

                    // logout
                    Zend_Session::start();
                    Zend_Session::destroy();

                } else if (array_key_exists('pending_cancel', $formData)) {
                    if (!$pending->cancel()) {
                        throw new Syj_Exception();
                    }
                    $title = $this->view->translate("request canceled");
                    $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/password_cancel.:suffix');
                } else {
                    $this->view->form = new Syj_Form_Pending_ResetPassword($pending, array('name' => 'pendingform'));
                    $title = $this->view->translate('password reset validation');
                }
            break;
            default:
                throw new Syj_Exception_Request();
            break;
        }

        $this->view->headTitle($title);
    }

}
