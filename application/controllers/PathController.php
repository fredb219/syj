<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class PathController extends Zend_Controller_Action
{
    public function indexAction() {
        $formData = $this->_helper->SyjPostData->getPostData('Syj_Form_Geom');

        $sessionStorage = Zend_Auth::getInstance()->getStorage();
        if ($sessionStorage->isEmpty()) {
            throw new Syj_Exception_Forbidden();
        }
        $sessionData = $sessionStorage->read();

        $user = new Syj_Model_User();
        $userMapper = new Syj_Model_UserMapper();
        if (!$userMapper->find($sessionData['user'], $user)) {
            // we could also throw a forbidden exception, but client session
            // should not contain reference to a non existent user. So, it's considered a bug.
            throw new Syj_Exception_Forbidden();
        }

        $decoder = new gisconverter\WKT();

        try {
            $geom = $decoder->geomFromText($formData["geom_data"]);
        } catch (gisconverter\CustomException $e) {
            throw new Syj_Exception_Request();
        }

        if ($geom::name != "LineString") {
            throw new Syj_Exception_Request();
        }

        $path = new Syj_Model_Path();
        $pathMapper = new Syj_Model_PathMapper();
        if (isset ($formData["geom_id"]) and $formData["geom_id"]) {
            if (!$pathMapper->find($formData["geom_id"], $path)) {
                throw new Syj_Exception_Request("unreferenced");
            }
        }
        $path->geom = $geom;
        if ($path->getId()) {
            if ($path->owner->id != $user->id) {
                throw new Syj_Exception_Forbidden();
            }
        } else {
            $path->owner = $user;
        }
        if (isset($formData["geom_title"])) {
            $path->title = $formData["geom_title"];
        }
        try {
            $pathMapper->save ($path);
        } catch(Zend_Db_Statement_Exception $e) {
            if ($e->getCode() == 23505) { // 23505: Unique violation throw new Syj_Exception_Request();
                $message = $e->getMessage();
                if (strpos($message, 'paths_geom_key') !== false) {
                    throw new Syj_Exception_Request("uniquepath");
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        $this->_helper->SyjApi->setBody($path->id);
    }
}
