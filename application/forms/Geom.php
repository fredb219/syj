<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Form_Geom extends Zend_Form implements Syj_Processor_Interface
{
    protected $_decorators = array(
                            'FormElements' => array('decorator' => 'FormElements', 'options' => null),
                            'Form' => array('decorator' => 'Form', 'options' => null)
                            );

    public function __construct($options = null) {
        $translator = $this->getTranslator();

        $data = array('Hidden', 'geom_data', array('required' => true, 'decorators' => array('ViewHelper', 'Errors')));

        $upload = array('File', 'geom_upload', array("label" => $translator->translate("choose route from a file"),
                                        'validators' => array(),
                                        'ignoreNoFile' => true,
                                        'decorators' => array(
                                            'File',
                                            'Errors',
                                            array('Label', array('separator' => '<br>')),
                                            array('HtmlTag', array('tag' => 'div', 'id' => 'geom_upload_container')),
                                        )));

        $title = array('Text', 'geom_title', array(
            'label' => __("optional title for this journey"),
            'attribs' => array('maxlength' => '40', 'size' => '20'),
            'validators' => array(new Zend_Validate_StringLength(0, 40)),
            'decorators' => array(
                                'ViewHelper',
                                'Errors',
                                array('Label', array('separator' => '<br>')),
                                array('HtmlTag', array('tag' => 'div', 'id' => 'geom_title_container')),
                                )));

        $anchor = $this->getView()->Anchor("termsofuse?format=raw",
                                           $translator->translate("terms of use"),
                                           array('id' => 'geom_termsofuse_anchor'));
        $text = $translator->translate("I've read and accepted %s");
        $text = vsprintf($text, $anchor);
        $touaccept = array('Checkbox', 'geom_accept', array("label" => $text,
                            'helper' => 'SyjFormCheckbox', // similar to FormCheckbox without a hidden input
                            'decorators' => array(
                                  'ViewHelper',
                                  'label',
                                  array('HtmlTag', array('tag' => 'div', 'id' => 'geom_accept_container', 'class' => 'logged-hide')))));

        $submit = array('Submit', 'geom_submit', array('label' => __("save"), 'decorators' => array(
                            'ViewHelper',
                            'Errors',
                            array('HtmlTag', array('tag' => 'br', 'openOnly' => true)))));
        $this->addElements(array($data, $upload, $title, $touaccept, $submit));

        $decorator = $this->geom_accept->getDecorator('Zend_Form_Decorator_Label');
        $decorator->setOption('escape', false);

        parent::__construct($options);
    }

    public function process(&$data) {
        $upload = null;
        if ($this->getValue("geom_upload")) {
            $file = $this->geom_upload;
            $upload = $file->getDestination() . DIRECTORY_SEPARATOR . $file->getValue();

            if (!isset($data["geom_data"]) || !$data["geom_data"]) {
                if (!file_exists($upload)) {
                    throw new Zend_Exception();
                }
                if (!@filesize($upload)) {
                    throw new Syj_Exception_InvalidGeomUpload();
                }
                $content = @file_get_contents ($upload);
                if ($content == false) {
                    throw new Zend_Exception();
                }
                $data['geom_data'] = $content;
            }
            @unlink ($upload);
        } else if (isset($_FILES['geom_upload']) and ($_FILES['geom_upload']['error']) == UPLOAD_ERR_INI_SIZE) {
            throw new Syj_Exception_ToolargeGeomUpload();
        } else {
            $this->removeElement("geom_upload");
        }
        return $this;
    }

}
