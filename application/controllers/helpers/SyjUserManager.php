<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjUserManager extends Zend_Controller_Action_Helper_Abstract
{
    // -1 for undeterminated, null for non logged, Syj_Model_User for a logged user
    protected static $_current = -1;

    static public function validate($username, $hash, $rememberme = false) {
        // TODO: try to make only one sql request
        $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable($adapter, 'users', 'pseudo', 'password');
        $authAdapter->setIdentity($username)->setCredential($hash);
        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($authAdapter);
        if (!$result->isValid()) {
            self::$_current = null;
            return false;
        }
        $userid = $authAdapter->getResultRowObject('id')->id;
        $userMapper = new Syj_Model_UserMapper();
        $user = new Syj_Model_User();
        if (!$userMapper->find($userid, $user)) {
            throw new Zend_Exception();
        }

        if (!isset ($_COOKIE['syj_user']) or (!isset ($_COOKIE['syj_hashpass']))) {
            if ($rememberme) {
                // cookie will be valid for 2 weeks
                $time = time () + 14 * 60 * 24 * 60;
            } else {
                $time = 0;
            }
            setcookie("syj_user", $username, $time, "", "", false, true);
            setcookie("syj_hashpass", $hash, $time, "", "", false, true);
        }
        self::$_current = $user;
        return true;
    }

    static public function logout() {
        self::$_current = null;
        if (isset ($_COOKIE['syj_user'])) {
            setcookie ('syj_user', "", time() - 3600, "" , "",false, true);
        }
        if (isset ($_COOKIE['syj_hashpass'])) {
            setcookie ('syj_hashpass', "", time() - 3600, "" , "",false, true);
        }
    }

    static public function current() {
        if (self::$_current === -1) {
            if ((!isset ($_COOKIE['syj_user'])) || (!isset ($_COOKIE['syj_hashpass']))
                 || (!self::validate($_COOKIE['syj_user'], $_COOKIE['syj_hashpass']))) {
                    self::logout();
            }
        }
        return self::$_current;
    }

    public function needsLogin() {
        if (self::current()) {
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
