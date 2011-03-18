<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjPostData extends Zend_Controller_Action_Helper_Abstract
{

    public function getPostData($form) {
        if (is_string($form) and class_exists($form)) {
            $form = new $form;
        }
        if (!$form instanceof Zend_Form) {
            throw new Zend_Exception();
        }

        if (!$this->getRequest()->isPost()) {
            throw new Syj_Exception_Request();
        }
        $data = $this->getRequest()->getPost();

        if ($form instanceof Syj_Processor_Interface) {
            $form->process($data);
        }

        if (!$form->isValid($data)) {
            throw new Syj_Exception_Request();
        }

        return $data;
    }

}
