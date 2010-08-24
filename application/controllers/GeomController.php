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

        $ext = "";
        $parts = explode('.', $idx);
        if (count($parts) >= 2) {
            $ext = end($parts);
            if (in_array($ext, array('kml', 'gpx', 'json'))) {
                $idx = implode('.', explode('.', $idx, -1));
            } else {
                $ext = "";
            }
        }

        if (!$pathMapper->find($idx, $path)) {
            if ($pathMapper->hasexisted($idx)) {
                $api->setCode(410);
            } else {
                $api->setCode(404);
            }
            return;
        }

        switch ($ext) {
            case 'kml':
                $this->kml($path);
            break;
            case 'gpx':
                $this->gpx($path);
            break;
            case 'json':
            default:
                $this->json($path);
                return;
            break;
        }
    }

    protected function kml(Syj_Model_Path $path) {
        $data = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;   // <? <-- vim syntax goes crazy
        $data .= '<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">';
        $data .= '<Placemark>';
        if ($path->creator) {
            $data .= '<atom:author><atom:name>' . htmlspecialchars($path->creator->pseudo) . '</atom:name></atom:author>';
        }
        $data .= '<name>' . htmlspecialchars($path->displayTitle) . '</name>';
        $data .= $path->geom->toKML();
        $data .= '</Placemark>';
        $data .= '</kml>';

        $api = $this->_helper->SyjApi;
        $api->setCheckIfNoneMatch(true)->setContentType('application/vnd.google-earth.kml+xml')->setBody($data);
    }

    protected function json(Syj_Model_Path $path) {
        $data = array('geom' => (string)$path->geom,
                  'title' => (string)$path->displayTitle);
        if ($path->creator) {
            $data['creator'] = (string)$path->creator->pseudo;
        }
        $api = $this->_helper->SyjApi;
        $api->setCheckIfNoneMatch(true)->setBodyJson($data);
    }

}
