<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class PathController extends Zend_Controller_Action
{
    public function indexAction() {
        $formData = $this->_helper->SyjPostData->getPostData('Syj_Form_Geom');
        $path = new Syj_Model_Path();

        $user = $this->_helper->SyjSession->user();
        if (!$user and !$formData["geom_accept"]) {
            throw new Syj_Exception_Request();
        }
        $path->creator = $user;
        $path->creatorIp = $this->getRequest()->getClientIp(true);

        $this->save($path, $formData);

        $redirecturl = "idx/" . (string)$path->id;
        if ($this->getRequest()->isXmlHttpRequest()) {
            $data = array('redirect' => $redirecturl);
            $this->_helper->SyjApi->setCode(201)->setBodyJson($data);
        } else {
            $this->_helper->SyjApi->setRedirect($redirecturl, 303);
        }
    }

    public function updateAction() {
        $formData = $this->_helper->SyjPostData->getPostData('Syj_Form_Geom');
        $path = $this->getPath();
        $this->save($path, $formData);
        $this->_helper->SyjApi->setCode(200); // we should use 204, but ie mangles 204 to 1223
    }

    public function deleteAction() {
        $path = $this->getPath();
        $pathMapper = new Syj_Model_PathMapper();
        $pathMapper->delete ($path);
        $this->_helper->SyjApi->setCode(200); // we should use 204, but ie mangles 204 to 1223
    }

    public function getPath() {
        $idx = $this->getRequest()->getUserParam('idx');
        $path = new Syj_Model_Path();
        $pathMapper = new Syj_Model_PathMapper();
        if (!$pathMapper->find($idx, $path)) {
            if ($pathMapper->hasexisted($idx)) {
                throw new Syj_Exception_NotFound('Gone', 410);
            } else {
                throw new Syj_Exception_NotFound('Not Found', 404);
            }
        }

        $user = $this->_helper->SyjSession->user();
        if (!$path->isCreator($user)) {
            throw new Syj_Exception_Forbidden();
        }
        return $path;
    }

    public function save(Syj_Model_Path $path, $formData) {
        /* setting geom property */
        $geom = null;
        foreach (array("WKT", "KML", "GPX", "geoJSON") as $dectype) {
            $classname = 'gisconverter\\' . $dectype;
            $decoder = new $classname();
            try {
                $geom = $decoder->geomFromText($formData["geom_data"]);
            } catch (Exception $e) {
            }
            if ($geom) {
                break;
            }
        }
        if (!$geom) {
            throw new Syj_Exception_InvalidGeomUpload();
        }

        if ($geom::name != "LineString") {
            throw new Syj_Exception_Request();
        }
        $path->geom = $geom;

        /* setting title property */
        if (isset($formData["geom_title"])) {
            $path->title = $formData["geom_title"];
        }


        /* now, saving !*/
        $pathMapper = new Syj_Model_PathMapper();
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
    }

}
