<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class LoginController extends Zend_Controller_Action
{
    public function init() {
        $this->view->headTitle($this->view->translate("login"));
        $this->_helper->SyjMedias->addScripts('login');
        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/form.css', 'all');
        $this->view->headLink()->appendStylesheet('css/login.css', 'all');
    }

    public function loginAction() {
        $form = new Syj_Form_Login(array('name' => 'loginform'));
        $request = $this->getRequest();
        $formData = $request->getPost();
        $this->view->form = $form;
        $httprequest = $request->isXmlHttpRequest();

        if (!$httprequest) {
            $this->_jsLocaleStrings();
        }

        if (empty ($formData) or !$form->isValid($formData)) {
            if ($httprequest) {
                throw new Syj_Exception_Request();
            } else {
                return;
            }
        }

        /* form has been filled */

        $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($adapter, 'users', 'pseudo', 'password');
        $authAdapter->setIdentity($formData['login_user'])
                ->setCredential(sha1($formData['login_password']));

        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($authAdapter);
        if (!$result->isValid()) {
            if ($httprequest) {
                throw new Syj_Exception_Forbidden();
            } else {
                $form->addError('Wrong login/password');
                return;
            }
        }

        $userid = $authAdapter->getResultRowObject('id')->id;
        $this->_helper->SyjSession->login($userid);
        $user = $this->_helper->SyjSession->user();

        if ($httprequest) {
            $api = $this->_helper->SyjApi->setCode(200);
            $data = array('pseudo' => $user->pseudo);

            $login_geom_id = $formData['login_geom_id'];
            if ($login_geom_id) {
                $path = new Syj_Model_Path();
                $pathMapper = new Syj_Model_PathMapper();
                if (!$pathMapper->find((int)$login_geom_id, $path)) {
                    throw new Syj_Exception_Request();
                }
                $data['iscreator'] = ($path->creator->id === $userid);
            } else {
                $data['iscreator'] = true;
            }
            $api->setBodyJson($data);
        } else {
            $this->redirect();
        }
    }

    public function logoutAction() {
        $this->_helper->SyjSession->logout();
        $this->redirect();
    }

    protected function redirect($target = null) {
        if (!isset($target)) {
            $target = $this->getRequest()->getQuery('redirect');
        }

        if (!isset($target)) {
            $target = $this->view->baseUrl();
        }
        if (!$target) {
            $target = '/';
        }

        $this->_helper->Redirector->gotoURL($target, array('prependBase' => false));
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'userEmptyWarn' => __("you must enter a login name"));
    }
}
