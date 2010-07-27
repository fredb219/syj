<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Pending_ValidateCreation extends Syj_Form_Pending
{
   protected function getActivateOptions() {
        $user = $this->_pending->user;

        $translator = $this->getTranslator();
        $activatetext = $translator->translate("Someone, probably you, has registered an account %s with email address %s on syj. To confirm this account creation, validate with following button.");
        $pseudo = '<strong>' . htmlspecialchars('"' . $user->pseudo . '"') . '</strong>';
        $email = '<strong>' . htmlspecialchars('"' . $user->email . '"') . '</strong>';
        $activatetext = vsprintf ($activatetext, array($pseudo, $email));

        return array(
                'label' => __("save"),
                'description' => $activatetext);
    }

    protected function getCancelOptions() {
        return array(
                'label' => __("delete"),
                'description' => __("To cancel account creation, press following button. The account and all its data will be deleted.")
                );
    }
}
