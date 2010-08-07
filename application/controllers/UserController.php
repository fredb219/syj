<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class UserController extends Zend_Controller_Action
{

    public function userAction() {
        $formData = $this->_helper->SyjPostData->getPostData('Syj_Form_User');

        // XXX: we validate email server side *only* so we don't want to
        // validate in SyjPostData. Then, we validate email outside Syj_Form_User
        $emailValidator = new Syj_Validate_EmailAddress();
        if (!$emailValidator->isValid($formData['user_email'])) {
            throw new Syj_Exception_InvalidEmail();
        }

        $user = new Syj_Model_User();
        $user->pseudo = $formData["user_pseudo"];
        $user->password = sha1($formData["user_password"]);
        $user->email = $formData["user_email"];

        if (Zend_Registry::isRegistered('Zend_Translate')) {
            $translator = Zend_Registry::get('Zend_Translate');
            $adapter = $translator->getAdapter();
            $locale = new Zend_Locale($adapter->getLocale());
            $user->lang = $locale->getLanguage();
        }

        $userMapper = new Syj_Model_UserMapper();

        try {
            $userMapper->save ($user);
        } catch(Zend_Db_Statement_Exception $e) {
            if ($e->getCode() == 23505) { // 23505: Unique violation throw new Syj_Exception_Request();
                $message = $e->getMessage();
                if (strpos($message, 'users_pseudo_key') !== false) {
                    throw new Syj_Exception_Request("uniquepseudo");
                } else if (strpos($message, 'users_email_key') !== false) {
                    throw new Syj_Exception_Request("uniqueemail");
                } else {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        $this->_helper->SyjSession->login($user->id);
        $this->_helper->SyjApi->setCode(200);
    }
}
