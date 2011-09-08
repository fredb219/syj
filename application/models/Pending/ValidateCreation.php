<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_Pending_ValidateCreation extends Syj_Model_Pending
{
    protected $_action = 'validate_creation';
    protected function _run() {
        return true;
    }

    public function _notify() {
        switch ($this->_notifications_number) {
            case 0:
                $subject = $this->translate("[SYJ] Please validate your account", $this->_user->lang);
                $text = $this->translate("Hi %user%, and welcome on syj

Your account is currently active, but you need to confirm your inscription by
following this link:
%hashurl%

If you do not not confirm your inscription within 7 days, your account will be
deleted.

Please do not reply this email. If you need to contact us, please use the form
contact at %contacturl%

Thanks,

Syj team", $this->_user->lang);
            break;

            case 1:
                $subject = $this->translate("[SYJ] Reminder: Validate your account", $this->_user->lang);
                $text = $this->translate("Hi %user%,

You need to validate your account on syj since 6 days. Otherwise, your
account will be deleted tomorrow. Please follow this link:
%hashurl%

Please do not reply this email. If you need to contact us, please use the form
contact at %contacturl%

Thanks,

Syj team", $this->_user->lang);

            break;

            default:
                $subject = $this->translate("[SYJ] Account deactivated", $this->_user->lang);
                $text = $this->translate("Hi %user%,

You have not validated your syj account on time. Therefore, your account have
been deleted. Nevertheless, you can still create a new account.

Please do not reply this email. If you need to contact us, please use the form
contact at %contacturl%

Regards,

Syj team", $this->_user->lang);
        }

        $text = str_replace('%user%', $this->_user->pseudo, $text);
        $text = str_replace('%hashurl%', $this->getHashUrl(), $text);
        $text = str_replace('%contacturl%', $this->getContactUrl(), $text);

        return $this->_sendMail($subject, $text);
    }

    protected function _cancel() {
        $mapper = new Syj_Model_UserMapper();
        $mapper->delete($this->_user);
        return true;
    }
}
