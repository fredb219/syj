<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class ErrorController extends Zend_Controller_Action
{
    protected function httpError($code) {
        $this->getResponse()->setHttpResponseCode($code);
    }

    public function init() {
        $this->_helper->SyjReset->resetPlaceHolders();
        $this->_helper->SyjMedias->addStyleSheets('error');
    }

    public function errorAction() {
        $error = $this->_getParam('error_handler');

        $error_code = 500; // default value: Internal Server Error
        switch ($error->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $error_code = 404; // Not Found
                break;
            default:
                if ($error->exception instanceof Syj_Exception_Request) {
                    $error_code = 400; // Bad Request
                } else if ($error->exception instanceof Syj_Exception_Forbidden) {
                    $error_code = 403; // Forbidden
                } else if ($error->exception instanceof Syj_Exception_NotFound) {
                    $error_code = $error->exception->getCode();
                }
                break;
        }
        $this->httpError($error_code);

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->crit($this->view->message, $error->exception);
        }

        if ($error_code == 400 and $error->request->isXmlHttpRequest()) {
            return $this->_helper->json(array('message' => $error->exception->getMessage()));
        } else if ($error->exception instanceof Syj_Exception_InvalidGeomUpload) {
            // invalid file upload: we will redirect to main page
            $this->_helper->SyjReset->resetPlaceHolders();
            $this->_request->setControllerName('idx')->setActionName('error')->setDispatched(false);
            return;
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $error->exception;
        }

        $this->view->request   = $error->request;
        $this->view->isServerError = ($error_code >= 500 and $error_code < 600);
        $this->view->headTitle($this->view->translate("Oups, something went wrong"));
    }

    public function getLog() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasPluginResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

}
