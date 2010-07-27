<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class GeomController extends Zend_Controller_Action
{

    public function indexAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $idx = $request->idx;
        $pathMapper = new Syj_Model_PathMapper();
        $path = new Syj_Model_Path();

        $api = $this->_helper->SyjApi;

        if (!$pathMapper->find($idx, $path)) {
            if ($pathMapper->hasexisted($idx)) {
                $api->setCode(410);
            } else {
                $api->setCode(404);
            }
            return;
        }

        $api->setCheckIfNoneMatch(true)->setBody(json_encode(
            array('owner' => (string)$path->owner->pseudo, 'geom' => (string)$path->geom)
        ));
    }
}
