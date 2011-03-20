<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class AccountController extends Zend_Controller_Action
{

    public function init() {
        $this->_helper->SyjUserManager->needsLogin();
        $this->_helper->SyjMedias->addScripts('account');

        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/form.css', 'all');
        $this->view->headLink()->appendStylesheet('css/account.css', 'all');
        $this->view->headTitle($this->view->translate("my account"));
    }

    public function indexAction() {
        $user = $this->_helper->SyjUserManager->current();
        $request = $this->getRequest();

        $form = new Syj_Form_Account(array('name' => 'accountform'));
        $formData = $request->getPost();

        $valid = false;
        if (!empty($formData) and $form->isValid($formData)) {
            $valid = true;
            if ($user->password != sha1($formData['account_password_current'])) {
                $valid = false;
                $form->account_password_current->addError(__("Wrong password"));
            }
            $user->email = $formData['account_email'];
            $user->password = sha1($formData['account_password']);
            $userMapper = new Syj_Model_UserMapper();

            try {
                $userMapper->save ($user);
            } catch(Zend_Db_Statement_Exception $e) {
                if ($e->getCode() == 23505) { // 23505: Unique violation throw new Syj_Exception_Request();
                    $message = $e->getMessage();
                    if (strpos($message, 'users_email_key') !== false) {
                        $valid = false;
                        $form->account_email->addError(__("an user is already registered with this email"));
                    } else {
                        throw $e;
                    }
                } else {
                    throw $e;
                }
            }
        }

        if ($valid) {
            $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/success.:suffix');
            return;
        }

        if (empty($formData)) {
            $form->account_email->setValue($user->email);
        } else {
            $form->account_email->setValue($formData['account_email']);
        }

        $this->_jsLocaleStrings();
        $this->view->form = $form;
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'notEmptyField' => __("Value is required"),
            'passwordNoMatchWarn' => __("Password do not match"),
            'passwordLenghtWarn' => array(__("At least %d characters"), 6),
            'nochangeWarn' => __("You have made no change"),
            );
    }
}
