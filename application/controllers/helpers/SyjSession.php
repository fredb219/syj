<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjSession extends Zend_Controller_Action_Helper_Abstract
{
    protected static $cache = array();

    static public function login($userid) {
        $userMapper = new Syj_Model_UserMapper();
        $user = new Syj_Model_User();
        if (!$userMapper->find($userid, $user)) {
            throw new Zend_Exception();
        }

        $storage = Zend_Auth::getInstance()->getStorage();
        $storage->clear();
        $storage->write(array('user' => $user->id));
        Zend_Session::rememberMe(); // zend default expiration delay is 2 weeks. Ok, use that value
    }

    static public function logout() {
        Zend_Session::start();
        Zend_Session::destroy();
    }

    static public function user() {
        try {
            $sessionStorage = Zend_Auth::getInstance()->getStorage();
        } catch(Exception $e) {
            return null;
        }
        $sessionData = $sessionStorage->read();
        if ($sessionStorage->isEmpty()) {
            return null;
        }

        $id = $sessionData['user'];
        if (!isset($id)) {
            return null;
        }
        if (isset (self::$cache[$id])) {
            return self::$cache[$id];
        }

        $userMapper = new Syj_Model_UserMapper();
        $user = new Syj_Model_User();
        if ($userMapper->find($id, $user)) {
            self::$cache[$id] = $user;
            return $user;
        } else {
            self::logout();
            return null;
        }
    }

    public function needsLogin() {
        $user = self::user();
        if ($user) {
            return;
        }
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        $request = $this->getRequest();

        $encodeduri = implode('/', array_map('urlencode', explode('/', $request->getRequestUri())));
        $loginurl = $view->addParamToUrl($view->baseUrl() . '/' . 'login', 'redirect', $encodeduri);
        $translator = Zend_Registry::get('Zend_Translate');
        $lang = $request->getQuery('lang');
        if ($lang) {
            $adapter = $translator->getAdapter();
            if ($adapter->isAvailable($lang)) {
                $loginurl = $view->addParamToUrl($loginurl, 'lang', $lang);
            }
        }
        $this->getActionController()->getHelper('Redirector')->gotoURL($loginurl, array('prependBase' => false));
    }
}
