<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
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

        $storage = new Zend_Session_Namespace('userSettings');
        $storage->user = $user->id;
        Zend_Session::rememberMe();
    }

    static public function logout() {
        $storage = new Zend_Session_Namespace('userSettings');
        unset($storage->user);
        Zend_Session::rememberMe();
    }

    static public function user() {
        $storage = new Zend_Session_Namespace('userSettings');
        $id = $storage->user;
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

        $encodeduri = $view->UriPath(true);
        $loginurl = $view->addParamToUrl($view->baseUrl() . '/' . 'login', 'redirect', $encodeduri);
        $translator = Zend_Registry::get('Zend_Translate');
        $this->getActionController()->getHelper('Redirector')->gotoURL($loginurl, array('prependBase' => false));
    }
}
