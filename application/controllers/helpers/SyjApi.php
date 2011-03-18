<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjApi extends Zend_Controller_Action_Helper_Abstract
{
    protected $_contentType = 'text/plain';
    protected $_checkIfNoneMatch = false;
    protected $_body = '';
    protected $_code = 200;
    protected $_redirect = '';

    public function init() {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setNoRender();
        $layout = Zend_Layout::getMvcInstance();
        if (null !== $layout) {
            $layout->disableLayout();
        }
    }

    public function setContentType($contentType) {
        $this->_contentType = (string)$contentType;
        return $this;
    }

    public function getContentType() {
        $this->_contentType = $contentType;
    }

    public function setBodyJson($data) {
        $this->setBody(json_encode($data))
             ->setContentType('application/json');
        return $this;
    }

    public function setBody($body) {
        $this->_body = (string)$body;
        return $this;
    }

    public function getBody() {
        return $body;
    }

    public function setCode($code) {
        $this->_code = (int)$code;
        return $this;
    }

    public function getCode() {
        return $this->_code;
    }

    public function setRedirect($url, $code = 0) {
        $this->_redirect = (string)$url;
        if (is_numeric($code) && (int)$code >= 300 && (int)$code < 400) {
            $this->_code;
        } else if (!isset($this->_code)) {
            $this->code = 301;
        }
        return $this;
    }

    public function getRedirect() {
        return $this->_redirect;
    }

    public function setCheckIfNoneMatch($check) {
        $this->_checkIfNoneMatch = (boolean)$check;
        return $this;
    }

    public function getCheckIfNoneMatch() {
        return $this->_checkIfNoneMatch;
    }

    public function postDispatch() {
        $response = $this->getResponse();

        $response->setHeader('Content-Type', $this->_contentType)
                 ->setHeader('Content-Length', strlen($this->_body));

        if ($this->_checkIfNoneMatch) {
            $request = $this->getRequest();

            $etag = md5 ($this->_body);
            if ($request->getServer("HTTP_IF_NONE_MATCH") == $etag) {
                $response->setHttpResponseCode(304);
                return;
            }

            // no-cache is needed otherwise IE does not try to get new version.
            $response->setHeader ('Cache-control', 'no-cache, must-revalidate');
            $response->setHeader ('Etag', $etag);
        }

        if ($this->_redirect) {
            $response->setHeader ('Location', $this->_redirect);
        }

        $response->setHttpResponseCode($this->_code)
                 ->setBody($this->_body);
    }
}
?>
