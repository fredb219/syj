<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class ContactController extends Zend_Controller_Action
{

    public function init() {
        $this->_helper->SyjMedias->addScripts('contact');
        $this->view->headLink()->appendStylesheet('css/generic.css', 'all');
        $this->view->headLink()->appendStylesheet('css/form.css', 'all');
        $this->view->headLink()->appendStylesheet('css/contact.css', 'all');
        $this->view->headTitle($this->view->translate("contact form"));
    }

    protected function saveToFile(array $data) {
        $savePath = Zend_Controller_Front::getInstance()->getParam('emailSavingPath');
        if (!$savePath) {
            return;
        }
        $saveDir = dirname($savePath);
        if (is_file($saveDir) and !is_dir($saveDir)) {
            throw new Zend_Exception();
        }
        if (!is_dir($saveDir)) {
            if (@mkdir($saveDir, 0640, true) === false) {
                throw new Zend_Exception();
            }
        }
        $handle = @fopen($savePath, 'a');
        if ($handle === false) {
            throw new Zend_Exception();
        }
        fwrite($handle, "------\n");
        fwrite($handle, "mail sent by " . $data['contact_email'] . "\n");
        fwrite($handle, "on " . date('r') . "\n");
        fwrite($handle, "subject: " . $data['contact_subject'] . "\n");
        fwrite($handle, "\n");
        fwrite($handle, $data['contact_content']);
        fwrite($handle, "\n");
        fwrite($handle, "\n");
        fclose($handle);
    }

    protected function sendMail(array $data) {
        try {
            $this->saveToFile($data);
        } catch(Zend_Exception $e) {
            return false;
        }
        $to = Zend_Controller_Front::getInstance()->getParam('webmasterEmail');
        $subject = "[SYJ] " . $data["contact_subject"];
        $content = $data["contact_content"];
        $from = $data["contact_email"];

        $mail = new Zend_Mail('utf-8');
        $mail->addTo($to)
             ->setSubject($subject)
             ->setBodyText($content)
             ->setFrom($from);

        try {
            $mail->send();
        } catch(Exception $e) {
            return false;
        }
        return true;
    }

    public function indexAction() {
        $form = new Syj_Form_Contact(array('name' => 'contactform'));

        $request = $this->getRequest();
        $formData = $request->getPost();

        if (!empty($formData) and $form->isValid($formData)) {
            if ($this->sendMail($form->getValues())) {
                $this->_helper->ViewRenderer->setViewScriptPathSpec(':controller/success.:suffix');
                return;
            } else {
                $this->view->sendError = true;
            }
        }

        if (empty($formData)) {
            $user = $this->_helper->SyjUserManager->current();
            if ($user) {
                $form->contact_email->setValue($user->email)
                                    ->setAttrib('readonly', 'true');
            }

            $subject = $request->getQuery('subject');
            if (isset($subject)) {
                foreach ($form->contact_subject->getValidators() as $key => $validator) {
                    if (!$validator->isValid($subject)) {
                        $subject = null;
                        break;
                    }
                }
            }
            $form->contact_subject->setValue($subject);

            $content = $request->getQuery('content');
            if (isset($content)) {
                foreach ($form->contact_content->getValidators() as $key => $validator) {
                    if (!$validator->isValid($content)) {
                        $content = null;
                        break;
                    }
                }
            }
            $form->contact_content->setValue(isset($content) ? $content : $this->view->translate("Hi,"));
        }

        $this->_jsLocaleStrings();
        $this->view->form = $form;
    }

    protected function _jsLocaleStrings() {
        $this->view->jslocales = array(
            'notEmptyField' => __("Value is required"),
            'invalidMail' => __("Invalid email"));
    }
}
