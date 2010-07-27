<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class IdxController extends Zend_Controller_Action
{

    public function init() {
        $this->view->headScript()->appendFile('js/OpenLayers.js');
        $this->view->headScript()->appendFile('js/ModifiablePath.js');
        $this->view->headScript()->appendFile('js/prototype.js');
        $this->view->headScript()->appendFile('js/simplebox.js');
        $this->view->headScript()->appendFile('js/closebtn.js');
        $this->view->headScript()->appendFile('js/deck.js');
        $this->view->headScript()->appendFile('js/ajaxize.js');
        $this->view->headScript()->appendFile('js/message.js');
        $this->view->headScript()->appendFile('js/syj.js');
        $this->view->headLink()->appendStylesheet('css/openlayers/style.css');
        $this->view->headLink()->appendStylesheet('css/generic.css');
        $this->view->headLink()->appendStylesheet('css/syj.css');
    }

    public function indexAction() {
        $url = $this->getRequest()->getUserParam('url');

        $geomform = new Syj_Form_Geom(array('name' => 'geomform', 'action' => 'path'));
        $loginform = new Syj_Form_Login(array('name' => 'loginform', 'action' => 'login'));
        $userform = new Syj_Form_User(array('name' => 'userform', 'action' => 'user'));
        $newpwdform = new Syj_Form_Newpwd(array('name' => 'newpwdform', 'action' => 'newpwd'));

        if (isset($url)) {
            $pathMapper = new Syj_Model_PathMapper();
            $path = new Syj_Model_Path();
            if (!$pathMapper->findByUrl($url, $path)) {
                if (is_numeric($url) and $pathMapper->hasexisted($url)) {
                    throw new Syj_Exception_NotFound('Gone', 410);
                } else {
                    throw new Syj_Exception_NotFound('Not Found', 404);
                }
            }
            $title = $path->displayTitle;
            $this->view->path = $path;
            $geomform->geom_title->setValue($path->title);
            $geomform->geom_data->setValue((string)$path->geom);
            $geomform->geom_id->setValue((string)$path->id);
            $loginform->login_geom_id->setValue((string)$path->id);
        } else {
            $extent = new phptojs\JsObject('gMaxExtent', $this->_helper->syjGeoip($this->getRequest()->getClientIp(true)));
            $this->view->headScript()->prependScript((string) $extent);
            $title = "Show your journey";
        }

        $this->_jsLoggedInfo(isset($url) ? $path: null);
        $this->_jsLocaleStrings();
        $this->view->headTitle($title);
        $this->view->geomform = $geomform;
        $this->view->loginform = $loginform;
        $this->view->userform = $userform;
        $this->view->newpwdform = $newpwdform;
    }

    protected function _jsLoggedInfo(Syj_Model_Path $path = null) {
        $loggedinfo = new phptojs\JsObject('gLoggedInfo', array('connections' => 0));

        $sessionStorage = Zend_Auth::getInstance()->getStorage();
        $sessionData = $sessionStorage->read();

        if ($sessionStorage->isEmpty()) {
            $loggedinfo->logged = false;
        } else {
            $userMapper = new Syj_Model_UserMapper();
            $obj = new Syj_Model_User();
            if ($userMapper->find($sessionData['user'], $obj)) {
                $loggedinfo->logged = true;
            } else {
                // non existent user
                Zend_Session::start();
                Zend_Session::destroy();
                $loggedinfo->logged = false;
            }
        }

        if (isset($path)) {
            if ($path->owner->id == $sessionData['user']) {
                $loggedinfo->isowner = true;
            } else {
                $loggedinfo->isowner = false;
            }
            $loggedinfo->ownername = $this->view->escape((string)$path->owner->pseudo);
        } else {
            $loggedinfo->isowner = true;
        }

        $this->view->headScript()->prependScript((string) $loggedinfo);
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'saveSuccess' => __("save took place successfully"),
            'requestError' => __("server did not understood request. That's probably caused by a bug in SYJ"),
            'UnreferencedError' => __("path did not exist in the server. May be it has been already deleted"),
            'uniquePathError' => __("similar path seems to already exist. Please do not create two exactly identical paths"),
            'notReachedError' => __("server could not be reached"),
            'serverError' => __("there was a server error"),
            'unknownError' => __("there was an unknown error"),
            'userEmptyWarn' => __("you must enter a login name"),
            'loginSuccess' => __("Login correct"),
            'loginFailure' => __("Wrong login/password"),
            'loginNeeded' => __("You need to login before retrying to save"),
            'cookiesNeeded' => __("You need to have cookies enabled to login to SYJ"),
            'passwordEmptyWarn' => __("you must enter a password"),
            'passwordNoMatchWarn' => __("Password do not match"),
            'acceptTermsofuseWarn' => __("You must accept terms of use"),
            'emailEmptyWarn' => __("you must enter an email"),
            'emailInvalidWarn' => __("invalid email"),
            'invalidPseudo' => __("pseudo must only contain letters, digits, dots or underscores"),
            'uniqueUserError' => __("unavailable pseudo"),
            'uniqueEmailError' => __("an user is already registered with this email"),
            'userSuccess' => __("Account created"),
            'newpwdSuccess' => __("A link to reset your password has been emailed to you"),
            'canResubmit' => __("Now, you can retry to save"),
            'routeBy' => __("route by"),
            'osmAttribution' => __("Map by <a href='http://openstreetmap.org/'>OpenStreetMap</a>")
            );
    }

}
