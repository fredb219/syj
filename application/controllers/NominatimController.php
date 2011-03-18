<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class NominatimController extends Zend_Controller_Action
{

    public function indexAction() {
        $search = $this->getRequest()->getQuery('nominatim-search');
        if (!isset($search)) {
            throw new Syj_Exception_Request();
        }

        $serverUrl = rtrim($this->view->serverUrl(), '/');
        $baseUrl = trim($this->view->baseUrl(), '/');
        $href = $serverUrl . '/' . ($baseUrl ? ($baseUrl . '/'): '');

        $client = new Zend_Http_Client('http://nominatim.openstreetmap.org/search', array(
                        'useragent' => ('Zend_Http_Client for ' . $href)));
        $client->setParameterGet(array('q' => $search,
                                       'format' => 'json',
                                       'email' => Zend_Controller_Front::getInstance()->getParam('webmasterEmail')));

        $response = $client->request();
        $data = json_decode($response->getBody());
        if (!$data) {
            $data = array();
        }
        $this->_helper->SyjApi->setBodyJson($data)->setCode($response->getStatus());
    }
}
