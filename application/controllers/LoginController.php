<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class LoginController extends Zend_Controller_Action
{
    public function init() {
        $this->view->headTitle($this->view->translate("login"));
        $this->view->headScript()->appendFile('js/prototype.js');
        $this->view->headScript()->appendFile('js/utils.js');
        $this->view->headScript()->appendFile('js/login.js');
        $this->view->headLink()->appendStylesheet('css/generic.css');
        $this->view->headLink()->appendStylesheet('css/login.css');
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
        $auth->getStorage()->write(array('user' => $userid));
        Zend_Session::rememberMe(); // zend default expiration delay is 2 weeks. Ok, use that value


        if ($httprequest) {
            $api = $this->_helper->SyjApi->setCode(200);

            $login_geom_id = $formData['login_geom_id'];
            if ($login_geom_id) {
                $path = new Syj_Model_Path();
                $pathMapper = new Syj_Model_PathMapper();
                if (!$pathMapper->find((int)$login_geom_id, $path)) {
                    throw new Syj_Exception_Request();
                }
                if ($path->owner->id === $userid) {
                    $api->setBody("1"); // owner of displayed geometry
                } else {
                    $api->setBody("0");
                }
            } else {
                $api->setBody("1"); // no geometry displayed: owner of the (future) geometry
            }
        } else {
            $this->redirect();
        }
    }

    public function logoutAction() {
        Zend_Session::start();
        Zend_Session::destroy();
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
