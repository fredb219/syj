<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Login extends Syj_Form_TableAbstract
{

    public function init() {
        $user = array('Text', 'login_user', array( 'label' => __("user"), 'required' => true));
        $pass = array('Password', 'login_password', array( 'label' => __("password")));


        $this->setMainElements(array($user, $pass))
             ->addElement('Hidden', 'login_geom_id', array( 'decorators' => array('ViewHelper')));

        $currentUri = $this->getView()->UriPath(true);
        $href = $this->getView()->addParamToUrl('newpwd', 'redirect', $currentUri, true);

        $anchor = $this->getView()->Anchor($href,
                                    $this->getTranslator()->translate("I forgot my password"),
                                    array('id' => 'newpwd_control_anchor'));

        $this->addElement('Submit', 'login_submit', array('label' => __("login"), 'description' => $anchor));
        $decorator = $this->login_submit->getDecorator('Description');
        $decorator->setOption('escape', false);
    }

}
