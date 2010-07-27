<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Pending_ResetPassword extends Syj_Form_Pending
{
    protected function getActivateOptions() {
        $user = $this->_pending->user;

        $translator = $this->getTranslator();
        $activatetext = $translator->translate("Hi %s. Someone, probably you, has asked to reset password for your account. To get a new password, validate with following button.");
        $pseudo = htmlspecialchars($user->pseudo);
        $activatetext = vsprintf ($activatetext, array($pseudo));

        return array(
                'label' => __("reset my password"),
                'description' => $activatetext);

    }

    protected function getCancelOptions() {
        return array(
                'label' => __("cancel request"),
                'description' => __("To cancel this request, press following button. Your password will not change.")
                );
    }
}
