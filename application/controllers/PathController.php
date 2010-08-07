<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class PathController extends Zend_Controller_Action
{
    public function indexAction() {
        $formData = $this->_helper->SyjPostData->getPostData('Syj_Form_Geom');
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

        $user = $this->_helper->SyjSession->user();
        if (!$user and !$formData["geom_accept"]) {
            throw new Syj_Exception_Request();
        }

        if ($path->getId()) {
            if (!$path->isCreator($user)) {
                throw new Syj_Exception_Request();
            }
        } else {
            $path->creator = $user;
        }

        if (isset($formData["geom_title"])) {
            $path->title = $formData["geom_title"];
        }
        $path->creatorIp = $this->getRequest()->getClientIp(true);
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
