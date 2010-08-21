<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class NewpwdController extends Zend_Controller_Action
{

    public function init() {
        $this->view->headScript()->appendFile('js/prototype.js');
        $this->view->headScript()->appendFile('js/newpwd.js');
        $this->view->headScript()->appendFile('js/utils.js');
        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/newpwd.css', 'all');
    }

    public function indexAction() {
        $form = new Syj_Form_Newpwd(array('name' => 'newpwdform'));
        $request = $this->getRequest();
        $formData = $request->getPost();
        $this->view->form = $form;
        $httprequest = $request->isXmlHttpRequest();

        if (!$httprequest) {
            $this->_jsLocaleStrings();
        }

        if (empty($formData)) {
            $loggeduser = $this->_helper->SyjSession->user();
            if ($loggeduser) {
                $form->newpwd_email->setValue($loggeduser->email)
                                    ->setAttrib('readonly', 'true');
            }
        }

        if (empty ($formData) or !$form->isValid($formData)) {
            if ($httprequest) {
                throw new Syj_Exception_Request();
            } else {
                return;
            }
        }

        /* form has been filled */
        $userMapper = new Syj_Model_UserMapper();
        $user = new Syj_Model_User();
        if ($userMapper->findByEmail($formData['newpwd_email'], $user)) {
            // if no user exist with posted email, pretend everything went correct
            $loggeduser = isset($loggeduser) ? $loggeduser: $this->_helper->SyjSession->user();
            if ($loggeduser and ($loggeduser != $user)) {
                throw new Syj_Exception_Request();
            }
            $pending = new Syj_Model_Pending_ResetPassword();
            $pending->setUser($user);
            if (!$pending->notify()) {
                throw new Zend_Exception();
            }
        }

        if ($httprequest) {
            $api = $this->_helper->SyjApi->setCode(200);
        } else {
            $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/success.:suffix');
        }

    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'notEmptyField' => __("Value is required"),
            );
    }
}
