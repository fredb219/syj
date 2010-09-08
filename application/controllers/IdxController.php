<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class IdxController extends Zend_Controller_Action
{

    public function init() {
        $this->_helper->SyjMedias->addScripts('idx');
        $this->view->headLink()->appendStylesheet('css/olstyle.css', "all");
        $this->view->headLink()->appendStylesheet('css/generic.css', "all");
        $this->view->headLink()->appendStylesheet('css/syj.css', "all");
    }

    public function rawmode(Syj_Model_Path $path) {
        $this->_helper->SyjReset->resetPlaceHolders();
        $this->_helper->SyjMedias->addScripts('rawidx');

        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/syjraw.css', 'all');
        $this->view->headTitle($path->displayTitle);

        $this->_jsRawLocaleStrings();
        if ($path->creator) {
            $this->view->jslocales['geomAttribution'] =
                        $this->view->translate('route by <strong>%s</strong>', (string)$path->creator->pseudo);
        }
        $jsgeom = new phptojs\JsObject('gInitialGeom', array('data' => (string) $path->geom));
        $this->view->headScript()->prependScript((string) $jsgeom);

        $this->view->rawmode = true;
        $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/raw.:suffix');
    }

    public function indexAction() {
        $url = $this->getRequest()->getUserParam('url');

        $geomform = new Syj_Form_Geom(array('name' => 'geomform'));
        $loginform = new Syj_Form_Login(array('name' => 'loginform', 'action' => 'login'));
        $userform = new Syj_Form_User(array('name' => 'userform', 'action' => 'user'));
        $newpwdform = new Syj_Form_Newpwd(array('name' => 'newpwdform', 'action' => 'newpwd'));

        if (isset($url)) {
            $pathMapper = new Syj_Model_PathMapper();
            $path = new Syj_Model_Path();
            if (!$pathMapper->findByUrl($url, $path)) {
                if (is_numeric($url) and $pathMapper->hasexisted($url)) {
                    $this->view->message = $this->view->translate("route has been deleted");
                    throw new Syj_Exception_NotFound('Gone', 410);
                } else {
                    $this->view->message = $this->view->translate("route does not exist");
                    throw new Syj_Exception_NotFound('Not Found', 404);
                }
            }

            if ($this->getRequest()->getQuery('format') == 'raw') {
                $this->rawmode($path);
                return;
            }

            $title = $path->displayTitle;
            $this->view->path = $path;
            $jsgeom = new phptojs\JsObject('gInitialGeom', array('data' => (string) $path->geom));
            $this->view->headScript()->prependScript((string) $jsgeom);
            $loginform->login_geom_id->setValue((string)$path->id);
            $geomform->geom_title->setValue($path->title);
        } else {
            $geomform->setAction('path');
            $extent = new phptojs\JsObject('gMaxExtent', $this->_helper->syjGeoip($this->getRequest()->getClientIp(true)));
            $this->view->headScript()->prependScript((string) $extent);
            $title = "Show your journey";
        }

        $this->_jsLoggedInfo(isset($url) ? $path: null);
        $this->_jsLocaleStrings();
        if (isset ($url) and $path->creator) {
            $this->view->jslocales['geomAttribution'] =
                        $this->view->translate('route by <strong>%s</strong>', (string)$path->creator->pseudo);
        }
        $this->view->headTitle($title);
        $this->view->geomform = $geomform;
        $this->view->loginform = $loginform;
        $this->view->userform = $userform;
        $this->view->newpwdform = $newpwdform;
        $this->view->loggedUser = $this->_helper->SyjSession->user();
    }

    protected function _jsLoggedInfo(Syj_Model_Path $path = null) {
        $loggedinfo = new phptojs\JsObject('gLoggedInfo', array('connections' => 0));

        $user = $this->_helper->SyjSession->user();
        if ($user) {
            $loggedinfo->logged = true;
        } else {
            $loggedinfo->logged = false;
        }

        if (isset($path)) {
            $loggedinfo->iscreator = $path->isCreator($user);
            $loggedinfo->pathid = (string)$path->id;
        } else {
            $loggedinfo->iscreator = true;
        }

        $this->view->headScript()->prependScript((string) $loggedinfo);
    }

    protected function _jsRawLocaleStrings() {
        $this->view->jslocales = array(
            'osmAttribution' => __("Map by <a href='http://openstreetmap.org/'>OpenStreetMap</a>"),
            );
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'saveSuccess' => __("save took place successfully"),
            'requestError' => __("server did not understood request. That's probably caused by a bug in SYJ"),
            'gonePathError' => __("route not referenced on the server. It has probably been deleted."),
            'uniquePathError' => __("similar path seems to already exist. Please do not create two exactly identical paths"),
            'notReachedError' => __("server could not be reached"),
            'serverError' => __("there was a server error"),
            'unknownError' => __("there was an unknown error"),
            'userEmptyWarn' => __("you must enter a login name"),
            'loginSuccess' => __("Login correct"),
            'loginFailure' => __("Wrong login/password"),
            'passwordEmptyWarn' => __("you must enter a password"),
            'passwordNoMatchWarn' => __("Password do not match"),
            'acceptTermsofuseWarn' => __("You must accept terms of use"),
            'emailEmptyWarn' => __("you must enter an email"),
            'emailInvalidWarn' => __("invalid email"),
            'uniqueEmailError' => __("an user is already registered with this email"),
            'userSuccess' => __("Account created"),
            'newpwdSuccess' => __("A link to reset your password has been emailed to you"),
            'canResubmit' => __("Now, you can retry to save"),
            'routeBy' => __("route by"),
            'osmAttribution' => __("Map by <a href='http://openstreetmap.org/'>OpenStreetMap</a>"),
            'pseudoChecking' => __("checking availibilty"),
            'availablePseudo' => __("available pseudo"),
            'unavailablePseudo' => __("unavailable pseudo"),
            'editAction' => __("edit"),
            'createAction' => __("create"),
            'cloneAction' => __("duplicate"),
            'unsavedConfirmExit' => __("You have an unsaved route"),
            'notEmptyField' => __("Value is required"),
            'noResult' => __("no result"),
            'dragFileError' => __("could not analyze file content"),
            );
    }

}
