<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_Pending_ResetPassword extends Syj_Model_Pending
{
    protected $_action = 'reset_password';
    protected $_newpwd;
    protected function _run() {
        $this->_newpwd = pwdgen\PwdGenerator::generate();
        $this->_user->password = sha1($this->_newpwd);
        $userMapper = new Syj_Model_UserMapper();
        $userMapper->save($this->_user);
        return true;
    }
    public function _notify() {
        switch ($this->_notifications_number) {
            case 0:
                $subject = $this->translate("[SYJ] Reset your password", $this->_user->lang);
                $text = $this->translate("Hi %user%,

Someone, probably you, has asked to reset your password. If you want to reset
your password, please follow this link:
%hashurl%

If you do not not confirm within 2 days, your password will not be reset.

Please do not reply this email. If you need to contact us, please use the form
contact at %contacturl%

Thanks,

Syj team", $this->_user->lang);
            break;
        }

        $text = str_replace('%user%', $this->_user->pseudo, $text);
        $text = str_replace('%hashurl%', $this->getHashUrl(), $text);
        $text = str_replace('%contacturl%', $this->getContactUrl(), $text);

        return $this->_sendMail($subject, $text);
    }
    protected function _cancel() {
        return true;
    }

    public function getNewpwd() {
        return $this->_newpwd;
    }
}
